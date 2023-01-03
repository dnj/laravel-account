<?php

namespace dnj\Account\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
			'title' => $this->title,
			'user_id' => $this->user_id,
			'currency_id' => $this->currency_id,
			'balance' => $this->balance,
			'can_send' => $this->can_send,
			'can_receive' => $this->can_receive,
			'status' => $this->status,
		];
    }
}
