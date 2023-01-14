<?php

namespace dnj\Account\Http\Requests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\ModelHelpers;
use dnj\Currency\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountSearchRequest extends FormRequest
{
    use ModelHelpers;

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
            'title' => ['sometimes', 'required', 'string'],
            'currency_id' => ['sometimes', 'required', Rule::exists(Currency::class, 'id')],
            'user_id' => ['sometimes', 'required', Rule::exists($this->getUserModel(), 'id')],
            'can_send' => ['sometimes', 'required', 'boolean'],
            'can_receive' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(AccountStatus::class)],
            'created_from' => ['sometimes', 'required', 'date'],
            'created_to' => ['sometimes', 'required', 'date', 'after:created_from'],
            'balance_from' => ['sometimes', 'required', 'numeric'],
            'balance_to' => ['sometimes', 'required', 'numeric', 'gte:balance_from'],
        ];
    }
}
