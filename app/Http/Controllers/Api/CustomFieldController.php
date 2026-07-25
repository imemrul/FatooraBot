<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use App\Services\CustomFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function __construct(private readonly CustomFieldService $service) {}

    public function definitions(Request $request): JsonResponse
    {
        $request->validate(['entity_type' => 'required|in:invoice,client,product']);
        return response()->json(['definitions' => $this->service->getDefinitions($request->entity_type)]);
    }

    public function storeDefinition(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => 'required|in:invoice,client,product',
            'field_label' => 'required|string|max:100',
            'field_type' => 'required|in:text,number,date,select,boolean',
            'options' => 'nullable|array', 'is_required' => 'nullable|boolean', 'sort_order' => 'nullable|integer',
        ]);
        return response()->json(['definition' => $this->service->createDefinition($data)], 201);
    }

    public function updateDefinition(Request $request, CustomFieldDefinition $definition): JsonResponse
    {
        $data = $request->validate([
            'field_label' => 'required|string|max:100', 'field_type' => 'required|in:text,number,date,select,boolean',
            'options' => 'nullable|array', 'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer', 'is_active' => 'nullable|boolean',
        ]);
        return response()->json(['definition' => $this->service->updateDefinition($definition, $data)]);
    }

    public function destroyDefinition(CustomFieldDefinition $definition): JsonResponse
    {
        $this->service->deleteDefinition($definition);
        return response()->json(['message' => 'Deleted.']);
    }

    public function values(Request $request): JsonResponse
    {
        $request->validate(['entity_type' => 'required|string', 'entity_id' => 'required|integer']);
        return response()->json(['values' => $this->service->getValues($request->entity_type, $request->entity_id)]);
    }

    public function setValues(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|string', 'entity_id' => 'required|integer',
            'fields' => 'required|array',
        ]);
        $this->service->setValues($request->entity_type, $request->entity_id, $request->fields);
        return response()->json(['message' => 'Custom fields saved.']);
    }
}
