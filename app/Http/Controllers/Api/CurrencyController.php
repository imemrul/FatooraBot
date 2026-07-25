<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(private readonly CurrencyService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['currencies' => $this->service->list()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|size:3',
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:5',
            'rate_to_base' => 'required|numeric|min:0.000001',
        ]);

        $currency = $this->service->create($data);
        return response()->json(['currency' => $currency], 201);
    }

    public function update(Request $request, Currency $currency): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'symbol' => 'sometimes|string|max:5',
            'rate_to_base' => 'sometimes|numeric|min:0.000001',
            'is_active' => 'sometimes|boolean',
        ]);

        $currency = $this->service->update($currency, $data);
        return response()->json(['currency' => $currency]);
    }

    public function destroy(Currency $currency): JsonResponse
    {
        $this->service->delete($currency);
        return response()->json(['message' => 'Currency deleted.']);
    }

    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $result = $this->service->convert($data['amount'], $data['from'], $data['to'], $request->user()->company_id);
        return response()->json(['result' => $result, 'from' => $data['from'], 'to' => $data['to']]);
    }

    public function seedDefaults(Request $request): JsonResponse
    {
        $this->service->seedDefaults($request->user()->company_id);
        return response()->json(['message' => 'Default currencies seeded.', 'currencies' => $this->service->list()]);
    }
}
