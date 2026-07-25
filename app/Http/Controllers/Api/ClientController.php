<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ClientDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function __construct(private readonly ClientService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        return ClientResource::collection(
            $this->service->list(
                $request->integer('per_page', 15),
                $request->string('search')->toString() ?: null,
            )
        );
    }

    public function all(): JsonResponse
    {
        return response()->json([
            'data' => Client::orderBy('name')->get(['id', 'name', 'email', 'phone']),
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->service->create(
            ClientDTO::fromArray($request->validated())
        );

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $updated = $this->service->update(
            $client,
            ClientDTO::fromArray($request->validated())
        );

        return new ClientResource($updated);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        if ($client->invoices()->whereNotIn('status', ['draft', 'cancelled'])->exists()) {
            return response()->json([
                'message' => 'Cannot delete customer with active invoices.',
            ], 422);
        }

        $this->service->delete($client);

        return response()->json(null, 204);
    }

    public function ledger(Request $request, Client $client): AnonymousResourceCollection
    {
        $this->authorize('view', $client);

        return InvoiceResource::collection(
            $this->service->getLedger($client, $request->integer('per_page', 20))
        );
    }

    public function statement(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return response()->json(
            $this->service->getStatement($client)
        );
    }
}
