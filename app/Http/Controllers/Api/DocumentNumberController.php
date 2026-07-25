<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentNumberConfig;
use App\Services\DocumentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentNumberController extends Controller
{
    public function __construct(private readonly DocumentNumberService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['configs' => $this->service->list()]);
    }

    public function update(Request $request, DocumentNumberConfig $documentNumberConfig): JsonResponse
    {
        $data = $request->validate([
            'prefix' => 'required|string|max:20',
            'suffix' => 'nullable|string|max:20',
            'padding' => 'required|integer|min:1|max:10',
            'separator' => 'required|string|max:3',
            'include_year' => 'nullable|boolean',
            'next_number' => 'nullable|integer|min:1',
        ]);
        return response()->json(['config' => $this->service->update($documentNumberConfig, $data)]);
    }

    public function seedDefaults(Request $request): JsonResponse
    {
        $this->service->seedDefaults($request->user()->company_id);
        return response()->json(['configs' => $this->service->list(), 'message' => 'Defaults seeded.']);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate(['document_type' => 'required|string']);
        $config = $this->service->getOrCreate($request->document_type);

        $sep = $config->separator;
        $num = str_pad($config->next_number, $config->padding, '0', STR_PAD_LEFT);
        $parts = [$config->prefix];
        if ($config->include_year) $parts[] = now()->format('Y');
        $parts[] = $num;
        if ($config->suffix) $parts[] = $config->suffix;

        return response()->json(['preview' => implode($sep, $parts)]);
    }
}
