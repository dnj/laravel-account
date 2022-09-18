<?php

namespace dnj\Account\Contracts;

enum AccountStatus: int
{
    case ACTIVE = 1;
    case DEACTIVE = 2;
}
