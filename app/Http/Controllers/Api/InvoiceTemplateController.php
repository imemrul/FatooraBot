<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['templates' => InvoiceTemplate::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'layout' => 'required|in:standard,modern,minimal',
            'primary_color' => 'nullable|string|max:7', 'font' => 'nullable|string|max:50',
            'show_logo' => 'nullable|boolean', 'show_qr' => 'nullable|boolean',
            'show_payment_info' => 'nullable|boolean', 'bilingual' => 'nullable|boolean',
            'header_text' => 'nullable|string', 'footer_text' => 'nullable|string', 'is_default' => 'nullable|boolean',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        if (!empty($data['is_default'])) InvoiceTemplate::where('company_id', $request->user()->company_id)->update(['is_default' => false]);
        return response()->json(['template' => InvoiceTemplate::create($data)], 201);
    }

    public function update(Request $request, InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'layout' => 'required|in:standard,modern,minimal',
            'primary_color' => 'nullable|string|max:7', 'font' => 'nullable|string|max:50',
            'show_logo' => 'nullable|boolean', 'show_qr' => 'nullable|boolean',
            'show_payment_info' => 'nullable|boolean', 'bilingual' => 'nullable|boolean',
            'header_text' => 'nullable|string', 'footer_text' => 'nullable|string', 'is_default' => 'nullable|boolean',
        ]);
        if (!empty($data['is_default'])) InvoiceTemplate::where('company_id', $request->user()->company_id)->where('id', '!=', $invoiceTemplate->id)->update(['is_default' => false]);
        $invoiceTemplate->update($data);
        return response()->json(['template' => $invoiceTemplate->fresh()]);
    }

    public function destroy(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $invoiceTemplate->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
