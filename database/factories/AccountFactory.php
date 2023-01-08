<?php

namespace dnj\Account\Database\Factories;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\ModelHelpers;
use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Currency\Database\Factories\CurrencyFactory;
use dnj\Currency\Models\Currency;
use dnj\Number\Contracts\INumber;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    use ModelHelpers;

    protected $model = Account::class;

    public function definition()
    {
        $userModel = $this->getUserModel() ?? User::class;

        return [
            'title' => fake()->sentence(3),
            'user_id' => $userModel::factory(),
            'balance' => Number::fromInt(0),
            'holding' => Number::fromInt(0),
            'currency_id' => Currency::factory(),
            'can_send' => true,
            'can_receive' => true,
            'status' => AccountStatus::ACTIVE,
            'meta' => null,
        ];
    }

    public function withTitle(string $title)
    {
        return $this->state(fn () => [
            'title' => $title,
        ]);
    }

    public function withBalance(string|int|float|INumber $balance)
    {
        return $this->state(fn () => [
            'balance' => Number::fromInput($balance),
        ]);
    }

    public function withHolding(string|int|float|INumber $holding)
    {
        return $this->state(fn () => [
            'holding' => Number::fromInput($holding),
        ]);
    }

    public function withUserId(?int $userId)
    {
        return $this->state(fn () => [
            'user_id' => $userId,
        ]);
    }

    public function withoutUser()
    {
        return $this->state(fn () => [
            'user_id' => null,
        ]);
    }

    public function withCurrency(Currency|CurrencyFactory $currency)
    {
        return $this->state(fn () => [
            'currency_id' => $currency,
        ]);
    }

    public function withUSD()
    {
        return $this->withCurrency(Currency::factory()->asUSD());
    }

    public function withEUR()
    {
        return $this->withCurrency(Currency::factory()->asEUR());
    }

    public function cantSend(bool $canSend = false)
    {
        return $this->state(fn () => [
            'can_send' => $canSend,
        ]);
    }

    public function cantReceive(bool $canReceive = false)
    {
        return $this->state(fn () => [
            'can_receive' => $canReceive,
        ]);
    }

    public function withStatus(AccountStatus $status)
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }

    public function deactived()
    {
        return $this->withStatus(AccountStatus::DEACTIVE);
    }

    public function withMeta(mixed $meta)
    {
        return $this->state(fn () => [
            'meta' => $meta,
        ]);
    }
}
