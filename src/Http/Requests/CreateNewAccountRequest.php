<?php

namespace dnj\Account\Http\Requests;

use dnj\Account\ModelHelpers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateNewAccountRequest extends FormRequest
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
        $userExistsRule = $this->getUserModel() ?
            Rule::exists($this->getUserModel(), 'id') : null;

        return [
            'title' => ['required'],
            'currency_id' => ['required'],
            'meta' => ['array', 'nullable'],
            'user_id' => ['required', $userExistsRule],
            'can_send' => ['required', 'boolean'],
            'can_receive' => ['required', 'boolean'],
        ];
    }
}
