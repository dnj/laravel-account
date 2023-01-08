<?php

namespace dnj\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'created_from' => ['sometimes', 'required', 'date'],
            'created_to' => ['sometimes', 'required', 'date', 'after:created_from'],
            'amount_from' => ['sometimes', 'required', 'numeric'],
            'amount_to' => ['sometimes', 'required', 'numeric', 'gte:amount_from'],
        ];
    }
}
