<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface IAccount
{
    public function getID(): int;

    public function getTitle(): string;

    public function getUserID(): ?int;

    public function getCurrencyID(): int;

    public function getCreateTime(): int;

    public function getUpdateTime(): int;

    public function getBalance(): INumber;

    public function getHoldingBalance(): INumber;

    public function getAvailableBalance(): INumber;

    public function getCanSend(): bool;

    public function getCanReceive(): bool;

    public function getMeta(): ?array;

    public function getStatus(): AccountStatus;
}
