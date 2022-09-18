<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface ITransaction
{
    public function getID(): int;

    public function getFromAccountID(): int;

    public function getToAccountID(): int;

    public function getAmount(): INumber;

    public function getCreateTime(): int;

    public function getUpdateTime(): int;

    public function getMeta(): ?array;
}
