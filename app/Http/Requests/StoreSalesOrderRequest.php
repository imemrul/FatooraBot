<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canAccess('manage_invoices');
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'client_id' => ['required', 'integer', "exists:clients,id,company_id,{$companyId}"],
            'warehouse_id' => ['nullable', 'integer', "exists:warehouses,id,company_id,{$companyId}"],
            'order_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer', "exists:products,id,company_id,{$companyId}"],
        ];
    }
}
