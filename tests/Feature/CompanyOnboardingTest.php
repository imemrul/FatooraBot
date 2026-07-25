<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function ownerWithCompany(bool $onboarded = true): User
    {
        $company = $onboarded
            ? Company::factory()->create()
            : Company::factory()->notOnboarded()->create();

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('owner');

        return $user;
    }

    // ── Company profile ──

    public function test_owner_can_view_company_profile(): void
    {
        $user = $this->ownerWithCompany();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/company')
            ->assertOk()
            ->assertJsonPath('data.id', $user->company_id);
    }

    public function test_owner_can_update_company_profile(): void
    {
        $user = $this->ownerWithCompany();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/company', [
                'name' => 'Updated Trading LLC',
                'email' => 'updated@company.ae',
                'phone' => '+971501111111',
                'trade_license_number' => 'TL-999999',
                'tax_registration_number' => '100000000000003',
                'address' => '123 Sheikh Zayed Road',
                'city' => 'Dubai',
            ]);

        $response->assertOk()
            ->assertJsonPath('company.name', 'Updated Trading LLC')
            ->assertJsonPath('company.city', 'Dubai');

        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'name' => 'Updated Trading LLC',
        ]);
    }

    public function test_non_owner_cannot_update_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('accountant');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/company', [
                'name' => 'Hacked',
                'email' => 'hack@test.com',
                'phone' => '123',
                'address' => 'x',
                'city' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $user = $this->ownerWithCompany();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/company', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'address', 'city']);
    }

    // ── Onboarding flow ──

    public function test_completing_profile_sets_onboarded_at(): void
    {
        $user = $this->ownerWithCompany(onboarded: false);

        $this->assertNull($user->company->onboarded_at);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/company', [
                'name' => 'My Company',
                'email' => $user->company->email,
                'phone' => '+971501234567',
                'address' => '123 Main St',
                'city' => 'Dubai',
            ]);

        $this->assertNotNull($user->company->fresh()->onboarded_at);
    }

    public function test_onboarded_middleware_blocks_non_onboarded_company(): void
    {
        $user = $this->ownerWithCompany(onboarded: false);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/clients')
            ->assertForbidden()
            ->assertJsonPath('message', 'Please complete company setup first.');
    }

    public function test_onboarded_company_can_access_resources(): void
    {
        $user = $this->ownerWithCompany(onboarded: true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/clients')
            ->assertOk();
    }

    // ── Logo upload ──

    public function test_owner_can_upload_logo(): void
    {
        $user = $this->ownerWithCompany();
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $file]);

        $response->assertOk()
            ->assertJsonPath('message', 'Logo uploaded successfully.');

        $company = $user->company->fresh();
        $this->assertNotNull($company->logo_path);
        Storage::disk('public')->assertExists($company->logo_path);
    }

    public function test_uploading_new_logo_deletes_old_one(): void
    {
        $user = $this->ownerWithCompany();

        $first = UploadedFile::fake()->image('first.png', 200, 200);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $first]);

        $oldPath = $user->company->fresh()->logo_path;

        $second = UploadedFile::fake()->image('second.png', 200, 200);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $second]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->company->fresh()->logo_path);
    }

    public function test_owner_can_delete_logo(): void
    {
        $user = $this->ownerWithCompany();
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $file]);

        $path = $user->company->fresh()->logo_path;

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/company/logo')
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->company->fresh()->logo_path);
    }

    public function test_logo_upload_validates_file_type(): void
    {
        $user = $this->ownerWithCompany();
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_logo_upload_validates_file_size(): void
    {
        $user = $this->ownerWithCompany();
        $file = UploadedFile::fake()->image('huge.png')->size(3000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    // ── Company resource includes correct data ──

    public function test_company_resource_includes_onboarded_flag(): void
    {
        $user = $this->ownerWithCompany(onboarded: true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/company')
            ->assertOk()
            ->assertJsonPath('data.onboarded', true);
    }

    public function test_company_resource_includes_logo_url(): void
    {
        $user = $this->ownerWithCompany();
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/company/logo', ['logo' => $file]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/company')
            ->assertOk()
            ->assertJsonStructure(['data' => ['logo_url']]);
    }
}
