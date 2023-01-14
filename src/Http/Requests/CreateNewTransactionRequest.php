<?php

namespace dnj\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNewTransactionRequest extends FormRequest
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
            'from_id' => ['required'],
            'to_id' => ['required'],
        ];
    }
}
