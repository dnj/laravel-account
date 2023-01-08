<?php

namespace dnj\Account\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource {
	public function toArray($request)
	{
		$data = parent::toArray($request);
		$data['amount'] = $data['amount']->__toString();
		return $data;
	}
}