<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientContact;
use App\Services\ClientContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientContactController extends Controller
{
    public function __construct(private readonly ClientContactService $service) {}

    public function index(int $clientId): JsonResponse
    {
        return response()->json(['contacts' => $this->service->list($clientId)]);
    }

    public function store(Request $request, int $clientId): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20', 'role' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean', 'notes' => 'nullable|string',
        ]);
        $data['client_id'] = $clientId;
        return response()->json(['contact' => $this->service->create($data)], 201);
    }

    public function update(Request $request, int $clientId, ClientContact $contact): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20', 'role' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean', 'notes' => 'nullable|string',
        ]);
        return response()->json(['contact' => $this->service->update($contact, $data)]);
    }

    public function destroy(int $clientId, ClientContact $contact): JsonResponse
    {
        $this->service->delete($contact);
        return response()->json(['message' => 'Deleted.']);
    }
}
