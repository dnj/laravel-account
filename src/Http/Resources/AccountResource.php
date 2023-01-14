<?php

namespace dnj\Account\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->resource->load(['user', 'currency']);
        $data = parent::toArray($request);
        $data['balance'] = isset($data['balance']) ?? $data['balance']->__toString();

        return $data;
    }
}
