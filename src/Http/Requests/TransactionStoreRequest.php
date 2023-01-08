<?php

namespace dnj\Account\Http\Requests;

use dnj\Account\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionStoreRequest extends FormRequest
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
            'from_id' => ['required', Rule::exists(Account::class, 'id')],
            'to_id' => ['required', Rule::exists(Account::class, 'id')],
            'amount' => ['required', 'string'],
            'meta' => ['nullable'],
            'force' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
