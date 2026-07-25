<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ClientDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly ClientService $service,
        private readonly WebhookService $webhooks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $clients = Client::latest()
            ->when($request->has('search'), fn ($q) => $q->where('name', 'ilike', '%' . $request->input('search') . '%'))
            ->paginate($request->integer('per_page', 25));

        return CustomerResource::collection($clients)->response();
    }

    public function show(Client $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_registration_number' => ['nullable', 'string', 'max:15'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $client = $this->service->create(ClientDTO::fromArray($validated));

        $this->webhooks->dispatch(
            $request->user()->company_id,
            'customer.created',
            (new CustomerResource($client))->resolve(),
        );

        return (new CustomerResource($client))->response()->setStatusCode(201);
    }

    public function update(Request $request, Client $customer): CustomerResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_registration_number' => ['nullable', 'string', 'max:15'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $updated = $this->service->update($customer, ClientDTO::fromArray($validated));

        $this->webhooks->dispatch(
            $request->user()->company_id,
            'customer.updated',
            (new CustomerResource($updated))->resolve(),
        );

        return new CustomerResource($updated);
    }
}
