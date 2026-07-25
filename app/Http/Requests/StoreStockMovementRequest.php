<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canAccess('manage_inventory');
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'product_id' => ['required', 'integer', "exists:products,id,company_id,{$companyId}"],
            'warehouse_id' => ['required', 'integer', "exists:warehouses,id,company_id,{$companyId}"],
            'type' => ['required', 'string', 'in:stock_in,stock_out,transfer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'to_warehouse_id' => ['required_if:type,transfer', 'nullable', 'integer', "exists:warehouses,id,company_id,{$companyId}", 'different:warehouse_id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_warehouse_id.required_if' => 'Destination warehouse is required for transfers.',
            'to_warehouse_id.different' => 'Destination must differ from source warehouse.',
        ];
    }
}
