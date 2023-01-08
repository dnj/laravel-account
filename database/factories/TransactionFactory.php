<?php

namespace dnj\Account\Database\Factories;

use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Number\Contracts\INumber;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'from_id' => Account::factory(),
            'to_id' => Account::factory(),
            'amount' => Number::fromFloat(fake()->randomFloat(3, 1, 500000), 3),
            'meta' => null,
        ];
    }

    public function withFromAccount(Account $from)
    {
        return $this->state(fn () => [
            'from_id' => $from->getID(),
        ]);
    }

    public function withToAccount(Account $to)
    {
        return $this->state(fn () => [
            'to_id' => $to->getID(),
        ]);
    }

    public function withAmount(INumber $amount)
    {
        return $this->state(fn () => [
            'amount' => $amount,
        ]);
    }

    public function withMeta(mixed $meta)
    {
        return $this->state(fn () => [
            'meta' => $meta,
        ]);
    }
}
