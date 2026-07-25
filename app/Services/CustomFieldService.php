<?php

namespace App\Services;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomFieldService
{
    public function getDefinitions(string $entityType): Collection
    {
        return CustomFieldDefinition::where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function createDefinition(array $data): CustomFieldDefinition
    {
        $data['field_name'] = Str::snake($data['field_label']);
        return CustomFieldDefinition::create($data);
    }

    public function updateDefinition(CustomFieldDefinition $def, array $data): CustomFieldDefinition
    {
        if (isset($data['field_label'])) $data['field_name'] = Str::snake($data['field_label']);
        $def->update($data);
        return $def->fresh();
    }

    public function deleteDefinition(CustomFieldDefinition $def): void
    {
        $def->values()->delete();
        $def->delete();
    }

    public function getValues(string $entityType, int $entityId): Collection
    {
        return CustomFieldValue::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('company_id', Auth::user()->company_id)
            ->with('definition:id,field_label,field_type')
            ->get();
    }

    public function setValues(string $entityType, int $entityId, array $fields): void
    {
        $companyId = Auth::user()->company_id;

        foreach ($fields as $defId => $value) {
            CustomFieldValue::updateOrCreate(
                ['custom_field_definition_id' => $defId, 'entity_type' => $entityType, 'entity_id' => $entityId],
                ['company_id' => $companyId, 'value' => $value],
            );
        }
    }
}
