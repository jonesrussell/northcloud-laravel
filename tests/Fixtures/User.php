<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use JonesRussell\NorthCloud\Concerns\IsAdministrator;

class User extends Authenticatable
{
    use IsAdministrator;

    protected $guarded = [];

    protected $table = 'users';
}
