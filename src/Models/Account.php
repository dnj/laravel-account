<?php

namespace dnj\Account\Models;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\IAccount;
use dnj\Account\Database\Factories\AccountFactory;
use dnj\Account\ModelHelpers;
use dnj\Currency\Models\Currency;
use dnj\Number\Contracts\INumber;
use dnj\Number\Laravel\Casts\Number;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model implements IAccount
{
    use ModelHelpers;
    use HasFactory;

    protected static function newFactory()
    {
        return AccountFactory::new();
    }

    protected $casts = [
        'balance' => Number::class,
        'holding' => Number::class,
        'meta' => 'array',
        'status' => AccountStatus::class,
    ];

    public function user()
    {
        $model = $this->getUserModel();
        if (null === $model) {
            throw new \Exception('No user model is configured under account.user_model config');
        }

        return $this->belongsTo($model);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function holdings()
    {
        return $this->hasMany(Holding::class);
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUserID(): ?int
    {
        return $this->user_id;
    }

    public function getUser(): ?Authenticatable
    {
        return $this->user;
    }

    public function getCurrencyID(): int
    {
        return $this->currency_id;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getCreateTime(): int
    {
        return $this->created_at->getTimestamp();
    }

    public function getUpdateTime(): int
    {
        return $this->modified_at?->getTimestamp() ?? $this->getCreateTime();
    }

    public function getBalance(): INumber
    {
        return $this->balance;
    }

    public function getHoldingBalance(): INumber
    {
        return $this->holding;
    }

    public function getAvailableBalance(): INumber
    {
        return $this->balance->sub($this->holding);
    }

    public function getCanSend(): bool
    {
        return $this->can_send;
    }

    public function getCanReceive(): bool
    {
        return $this->can_receive;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }
}
