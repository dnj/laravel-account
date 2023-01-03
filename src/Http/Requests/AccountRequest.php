<?php

namespace dnj\Account\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest {
	
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
			'account_id' => ['required'],
		];
	}
}