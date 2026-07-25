<?php

namespace App\Services;

use App\Models\ClientContact;
use Illuminate\Database\Eloquent\Collection;

class ClientContactService
{
    public function list(int $clientId): Collection
    {
        return ClientContact::where('client_id', $clientId)->orderByDesc('is_primary')->orderBy('name')->get();
    }

    public function create(array $data): ClientContact
    {
        if (!empty($data['is_primary'])) {
            ClientContact::where('client_id', $data['client_id'])->update(['is_primary' => false]);
        }
        return ClientContact::create($data);
    }

    public function update(ClientContact $contact, array $data): ClientContact
    {
        if (!empty($data['is_primary'])) {
            ClientContact::where('client_id', $contact->client_id)->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }
        $contact->update($data);
        return $contact->fresh();
    }

    public function delete(ClientContact $contact): void
    {
        $contact->delete();
    }
}
