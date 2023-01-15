<?php

namespace dnj\Account\Http\Requests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\ModelHelpers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountUpdateRequest extends FormRequest
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
            'user_id' => ['sometimes', 'required', Rule::exists($this->getUserModel(), 'id')],
            'meta' => ['sometimes', 'nullable'],
            'can_send' => ['sometimes', 'required', 'boolean'],
            'can_receive' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(AccountStatus::class)],
        ];
    }
}
