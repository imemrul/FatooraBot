<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createUser(array $overrides = []): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(array_merge(
            ['company_id' => $company->id],
            $overrides,
        ));
        $user->assignRole('owner');

        return $user;
    }

    public function test_user_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+971501234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Trading LLC',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token', 'message']);

        $this->assertDatabaseHas('companies', ['name' => 'Test Trading LLC']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('owner'));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'company_name']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->createUser(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Another Co',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $this->createUser([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', 'login@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser([
            'email' => 'fail@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'fail@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $company = Company::factory()->create();
        User::factory()->inactive()->create([
            'company_id' => $company->id,
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email_verified', true);
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        $user = $this->createUser(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset link sent to your email.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_fails_for_unknown_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_can_be_reset(): void
    {
        $user = $this->createUser(['email' => 'resetme@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password has been reset successfully.');

        // Verify new password works
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
        ]);

        $loginResponse->assertOk();
    }

    public function test_email_verification(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->unverified()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('owner');

        $this->assertNull($user->email_verified_at);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/verify');

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resend_verification_email(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $user = User::factory()->unverified()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('owner');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/resend');

        $response->assertOk();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_fails_if_already_verified(): void
    {
        $user = $this->createUser(); // verified by default

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/resend');

        $response->assertStatus(422);
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
