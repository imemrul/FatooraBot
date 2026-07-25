<?php

namespace App\Services;

use App\Models\AppNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function list(int $userId, ?bool $unreadOnly = false): LengthAwarePaginator
    {
        $query = AppNotification::where('user_id', $userId)->latest();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->paginate(30);
    }

    public function unreadCount(int $userId): int
    {
        return AppNotification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    public function markAsRead(string $id, int $userId): void
    {
        AppNotification::where('id', $id)->where('user_id', $userId)->update(['read_at' => now()]);
    }

    public function markAllRead(int $userId): int
    {
        return AppNotification::where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function send(int $companyId, int $userId, string $type, string $title, ?string $body = null, ?string $actionUrl = null): AppNotification
    {
        return AppNotification::send($companyId, $userId, $type, $title, $body, $actionUrl);
    }

    /**
     * Send to all users in a company (or filtered by role).
     */
    public function broadcast(int $companyId, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?string $role = null): int
    {
        $query = \App\Models\User::withoutGlobalScopes()->where('company_id', $companyId)->where('is_active', true);

        if ($role) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        $count = 0;
        $query->each(function ($user) use ($companyId, $type, $title, $body, $actionUrl, &$count) {
            AppNotification::send($companyId, $user->id, $type, $title, $body, $actionUrl);
            $count++;
        });

        return $count;
    }
}
