<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface IHoldingRecord
{
    public function getID(): int;

    public function getAccountID(): int;

    public function getAmount(): INumber;

    public function getCreateTime(): int;

    public function getUpdateTime(): int;

    public function getMeta(): ?array;
}
