<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Exceptions;

use InvalidArgumentException;

class RoleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $roleName)
    {
        return new static("A role `{$roleName}` already exists.");
    }
}
