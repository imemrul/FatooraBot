<?php

namespace App\Services;

use App\Models\Client;

class CreditControlService
{
    public function checkCredit(Client $client): array
    {
        $outstanding = $client->outstanding_balance;
        $limit = (float) $client->credit_limit;
        $available = $limit > 0 ? max(0, $limit - $outstanding) : null;
        $overLimit = $limit > 0 && $outstanding > $limit;

        return [
            'client_id' => $client->id,
            'credit_limit' => $limit,
            'outstanding' => $outstanding,
            'available_credit' => $available,
            'over_limit' => $overLimit,
            'credit_hold' => $client->credit_hold,
            'can_invoice' => !$client->credit_hold && !$overLimit,
        ];
    }

    public function toggleHold(Client $client): Client
    {
        $client->update(['credit_hold' => !$client->credit_hold]);
        return $client->fresh();
    }

    public function getAtRiskClients(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Client::where('company_id', $companyId)
            ->where('credit_limit', '>', 0)
            ->withSum(['invoices as outstanding' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled'])->selectRaw('SUM(total - paid_amount)')], 'total')
            ->get()
            ->filter(fn ($c) => (float) ($c->outstanding ?? 0) > (float) $c->credit_limit)
            ->values();
    }
}
