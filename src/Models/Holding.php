<?php

namespace dnj\Account\Models;

use dnj\Account\Contracts\IHoldingRecord;
use dnj\Number\Contracts\INumber;
use dnj\Number\Laravel\Casts\Number;
use dnj\UserLogger\Concerns\Loggable;
use Illuminate\Database\Eloquent\Model;

class Holding extends Model implements IHoldingRecord
{
    use Loggable;

    protected $casts = [
        'amount' => Number::class,
        'meta' => 'array',
    ];

    protected $table = 'accounts_holdings';

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getAccountID(): int
    {
        return $this->account_id;
    }

    public function getAmount(): INumber
    {
        return $this->amount;
    }

    public function getCreateTime(): int
    {
        return $this->created_at->getTimestamp();
    }

    public function getUpdateTime(): int
    {
        return $this->modified_at?->getTimestamp() ?? $this->getCreateTime();
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }
}
