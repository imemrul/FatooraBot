<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canAccess('manage_invoices');
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Message is too short to parse.',
        ];
    }
}
