<?php

namespace dnj\Account\Models;

use dnj\Account\Contracts\ITransaction;
use dnj\Account\Database\Factories\TransactionFactory;
use dnj\Number\Contracts\INumber;
use dnj\Number\Laravel\Casts\Number;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model implements ITransaction
{
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    use HasFactory;

    protected $casts = [
        'amount' => Number::class,
        'meta' => 'array',
    ];

    protected $table = 'accounts_transactions';

    public function getID(): int
    {
        return $this->id;
    }

    public function getFromAccountID(): int
    {
        return $this->from_id;
    }

    public function getToAccountID(): int
    {
        return $this->to_id;
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
