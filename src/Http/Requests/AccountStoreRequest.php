<?php

namespace dnj\Account\Http\Requests;

use dnj\Account\ModelHelpers;
use dnj\Currency\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountStoreRequest extends FormRequest
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
        $userExistRule = $this->getUserModel() ? Rule::exists($this->getUserModel(), 'id') : null;

        return [
            'title' => ['required', 'string'],
            'currency_id' => ['required', Rule::exists(Currency::class, 'id')],
            'user_id' => ['required', $userExistRule],
            'meta' => ['nullable'],
            'can_send' => ['required', 'boolean'],
            'can_receive' => ['required', 'boolean'],
        ];
    }
}
