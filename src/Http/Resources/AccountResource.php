<?php

namespace dnj\Account\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['balance'] = $data['balance']->__toString();

        return $data;
    }
}
