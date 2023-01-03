<?php

namespace dnj\Account\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class CreateNewAccountRequest extends FormRequest {
	
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
			'title' => ['required'],
			'currency_id' => ['required'],
			'meta' => ['array','nullable'],
		];
	}
}