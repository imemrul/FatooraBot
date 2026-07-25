<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['payment_methods' => PaymentMethod::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'type' => 'required|in:bank_transfer,cash,cheque,card,online',
            'instructions' => 'nullable|string', 'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50', 'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20', 'is_default' => 'nullable|boolean',
        ]);
        if (!empty($data['is_default'])) PaymentMethod::where('company_id', $request->user()->company_id)->update(['is_default' => false]);
        return response()->json(['payment_method' => PaymentMethod::create($data)], 201);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'type' => 'required|in:bank_transfer,cash,cheque,card,online',
            'instructions' => 'nullable|string', 'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50', 'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20', 'is_default' => 'nullable|boolean', 'is_active' => 'nullable|boolean',
        ]);
        if (!empty($data['is_default'])) PaymentMethod::where('company_id', $request->user()->company_id)->where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
        $paymentMethod->update($data);
        return response()->json(['payment_method' => $paymentMethod->fresh()]);
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
