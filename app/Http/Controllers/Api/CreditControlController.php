<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\CreditControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditControlController extends Controller
{
    public function __construct(private readonly CreditControlService $service) {}

    public function check(Client $client): JsonResponse
    {
        return response()->json($this->service->checkCredit($client));
    }

    public function toggleHold(Client $client): JsonResponse
    {
        $client = $this->service->toggleHold($client);
        return response()->json(['client' => $client, 'message' => $client->credit_hold ? 'Credit hold applied.' : 'Credit hold released.']);
    }

    public function atRisk(Request $request): JsonResponse
    {
        return response()->json(['clients' => $this->service->getAtRiskClients($request->user()->company_id)]);
    }
}
