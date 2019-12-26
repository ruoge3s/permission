<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Models;

use Hyperf\DbConnection\Model\Model;
use Qingliu\Permission\Traits\HasPermissions;
use Qingliu\Permission\Exceptions\RoleDoesNotExist;
use Qingliu\Permission\Exceptions\RoleAlreadyExists;
use Qingliu\Permission\Contracts\Role as RoleContract;
use Qingliu\Permission\Traits\RefreshesPermissionCache;;
use Hyperf\Database\Model\Relations\BelongsToMany;

/**
 * Class Role
 * @property BelongsToMany permissions
 * @package Qingliu\Permission\Models
 */
class Role extends Model implements RoleContract
{
    use HasPermissions;
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.roles'));
    }

    public static function create(array $attributes = [])
    {
        if (static::query()->where('name', $attributes['name'])->first()) {
            throw RoleAlreadyExists::create($attributes['name']);
        }
        return static::query()->create($attributes);
    }

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * @param string $name
     * @return RoleContract
     */
    public static function findByName(string $name): RoleContract
    {

        /** @var self $role */
        $role = static::query()->where('name', $name)->first();
        if ($role) {
            return $role;
        }

        throw RoleDoesNotExist::named($name);
    }

    public static function findById(int $id): RoleContract
    {
        /** @var self $role */
        $role = static::query()->where('id', $id)->first();
        if ($role) {
            return $role;
        }
        throw RoleDoesNotExist::withId($id);
    }

    /**
     * Find or create role by its name
     * @param string $name
     * @return RoleContract
     */
    public static function findOrCreate(string $name): RoleContract
    {
        /** @var self $role */
        $role = static::query()->where('name', $name)->first();
        if ($role) {
            return $role;
        }
        $role = static::query()->create(['name' => $name]);
        return $role;
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission);
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission);
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
