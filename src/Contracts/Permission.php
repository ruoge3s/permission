<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Contracts;

use Hyperf\Database\Model\Relations\BelongsToMany;

interface Permission
{
    /**
     * A permission can be applied to roles.
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     * @param string $name
     * @return static
     */
    public static function findByName(string $name): self;

    /**
     * Find a permission by its id.
     * @param int $id
     * @return static
     */
    public static function findById(int $id): self;

    /**
     * Find or Create a permission by its name.
     * @param string $name
     * @return static
     */
    public static function findOrCreate(string $name): self;
}
