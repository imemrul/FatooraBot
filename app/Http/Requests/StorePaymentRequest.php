<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canAccess('manage_invoices');
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        $maxAmount = $invoice->balance_due;

        return [
            'amount' => ['required', 'numeric', 'min:0.01', "max:{$maxAmount}"],
            'method' => ['required', 'string', 'in:bank_transfer,cash,cheque,card'],
            'reference' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Payment amount cannot exceed the balance due.',
        ];
    }
}
