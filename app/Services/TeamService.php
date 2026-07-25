<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class TeamService
{
    public function list(int $companyId, ?string $search = null): LengthAwarePaginator
    {
        $query = User::where('company_id', $companyId)
            ->withoutGlobalScopes()
            ->with('roles')
            ->latest();

        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'ilike', "%{$escaped}%")
                    ->orWhere('email', 'ilike', "%{$escaped}%");
            });
        }

        return $query->paginate(20);
    }

    public function invite(int $companyId, array $data, int $invitedBy): User
    {
        $user = User::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        AppNotification::send($companyId, $invitedBy, 'team_invite', 'Team member invited', "{$user->name} was added as {$data['role']}.", '/team');

        return $user->load('roles');
    }

    public function updateRole(int $companyId, int $userId, string $role): User
    {
        $user = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($userId);

        $user->syncRoles([$role]);

        return $user->load('roles');
    }

    public function toggleStatus(int $companyId, int $userId): User
    {
        $user = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($userId);

        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh()->load('roles');
    }

    public function remove(int $companyId, int $userId): void
    {
        $user = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($userId);

        $user->tokens()->delete();
        $user->delete();
    }
}
