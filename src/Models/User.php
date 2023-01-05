<?php

namespace dnj\Account\Models;
use Illuminate\Foundation\Auth\User as BaseUser;

class User extends BaseUser
{
	protected $table = 'users';
}