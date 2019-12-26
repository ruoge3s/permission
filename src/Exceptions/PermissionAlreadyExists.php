<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Exceptions;

use InvalidArgumentException;

class PermissionAlreadyExists extends InvalidArgumentException
{
    public static function create(string $permissionName)
    {
        return new static("A `{$permissionName}` permission already exists");
    }
}
