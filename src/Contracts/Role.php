<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Contracts;

use Hyperf\Database\Model\Relations\BelongsToMany;

interface Role
{
    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name.
     * @param string $name
     * @return static
     */
    public static function findByName(string $name): self;

    /**
     * Find a role by its id.
     * @param int $id
     * @return static
     */
    public static function findById(int $id): self;

    /**
     * Find or create a role by its name.
     * @param string $name
     * @return static
     */
    public static function findOrCreate(string $name): self;

    /**
     * Determine if the user may perform the given permission.
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool;
}
