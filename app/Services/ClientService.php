<?php

namespace App\Services;

use App\DTOs\ClientDTO;
use App\Models\Client;
use App\Models\Invoice;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function list(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Client::query()
            ->withSum(
                ['invoices as total_invoiced' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled'])],
                'total'
            )
            ->withSum(
                ['invoices as total_paid' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled'])],
                'paid_amount'
            )
            ->withCount(
                ['invoices as overdue_invoice_count' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled', 'paid'])->where('due_date', '<', now())]
            );

        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'ilike', "%{$escaped}%")
                    ->orWhere('email', 'ilike', "%{$escaped}%")
                    ->orWhere('phone', 'like', "%{$escaped}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(int $id): Client
    {
        return $this->repository->findOrFail($id);
    }

    public function create(ClientDTO $dto): Client
    {
        return $this->repository->create($dto->toArray());
    }

    public function update(Client $client, ClientDTO $dto): Client
    {
        return $this->repository->update($client, $dto->toArray());
    }

    public function delete(Client $client): bool
    {
        return $this->repository->delete($client);
    }

    public function getLedger(Client $client, int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::where('client_id', $client->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with('items')
            ->latest('issue_date')
            ->paginate($perPage);
    }

    public function getStatement(Client $client): array
    {
        $invoices = Invoice::where('client_id', $client->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('issue_date')
            ->get();

        $runningBalance = 0;
        $totalInvoiced = 0;
        $totalPaid = 0;
        $entries = [];

        foreach ($invoices as $inv) {
            $total = (float) $inv->total;
            $paid = (float) $inv->paid_amount;
            $totalInvoiced += $total;
            $totalPaid += $paid;

            $runningBalance += $total;
            $entries[] = [
                'date' => $inv->issue_date->toDateString(),
                'type' => 'invoice',
                'reference' => $inv->invoice_number,
                'debit' => $total,
                'credit' => 0,
                'balance' => round($runningBalance, 2),
                'status' => $inv->status,
            ];

            if ($paid > 0) {
                $runningBalance -= $paid;
                $entries[] = [
                    'date' => $inv->updated_at->toDateString(),
                    'type' => 'payment',
                    'reference' => $inv->invoice_number,
                    'debit' => 0,
                    'credit' => $paid,
                    'balance' => round($runningBalance, 2),
                    'status' => 'applied',
                ];
            }
        }

        return [
            'entries' => $entries,
            'total_invoiced' => round($totalInvoiced, 2),
            'total_paid' => round($totalPaid, 2),
            'outstanding_balance' => round($totalInvoiced - $totalPaid, 2),
        ];
    }
}
