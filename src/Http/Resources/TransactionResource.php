<?php

namespace dnj\Account\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource {
	
	
	public static $wrap = 'transaction';
	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
	 */
	public function toArray($request)
	{
		return [
			'id' => $this->id,
			'form_id' => $this->from_id,
			'to_id' => $this->to_id,
			'amount' => $this->amount,
			'meta' => $this->meta,
		];
	}
}