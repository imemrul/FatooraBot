<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('view_customers');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->company_id === $client->company_id
            && $user->canAccess('view_customers');
    }

    public function create(User $user): bool
    {
        return $user->canAccess('manage_customers');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->company_id === $client->company_id
            && $user->canAccess('manage_customers');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->company_id === $client->company_id
            && $user->canAccess('manage_customers');
    }
}
