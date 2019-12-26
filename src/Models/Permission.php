<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Models;

use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Collection;
use Qingliu\Permission\Traits\HasRoles;
use Hyperf\DbConnection\Model\Model;
use Qingliu\Permission\PermissionRegistrar;
use Qingliu\Permission\Traits\RefreshesPermissionCache;
use Qingliu\Permission\Exceptions\PermissionDoesNotExist;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Qingliu\Permission\Exceptions\PermissionAlreadyExists;
use Qingliu\Permission\Contracts\Permission as PermissionContract;

/**
 * Class Permission
 * @property BelongsToMany|Collection roles
 * @method Permission assignRole(...$roles)
 * @package Qingliu\Permission\Models
 */
class Permission extends Model implements PermissionContract
{
    use HasRoles;
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.permissions'));
    }

    public static function create(array $attributes = [])
    {
        $permission = static::getPermissions(['name' => $attributes['name']])->first();

        if ($permission) {
            throw PermissionAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * @param string $name
     * @return PermissionContract
     */
    public static function findByName(string $name): PermissionContract
    {
        $permission = static::getPermissions(['name' => $name])->first();
        if (!$permission) {
            throw PermissionDoesNotExist::create($name);
        }

        return $permission;
    }

    /**
     * Find a permission by its id.
     * @param int $id
     * @return Permission
     */
    public static function findById(int $id): PermissionContract
    {
        $permission = static::getPermissions(['id' => $id])->first();
        if ($permission) {
            return $permission;
        }
        throw PermissionDoesNotExist::withId($id);
    }

    /**
     * @param string $name
     * @return PermissionContract
     */
    public static function findOrCreate(string $name): PermissionContract
    {
        /** @var self $permission */
        $permission = static::getPermissions(['name' => $name])->first();
        if ($permission) {
            return $permission;
        }
        $permission = static::query()->create(['name' => $name]);
        return $permission;
    }

    /**
     * 获取树形的permission列表.
     * @param int||string $parentId 父级ID
     * @param bool $isUrl 是否是一个URL
     * @param Collection $permission 传入permission集合，如果不传将从所有的permission生成
     * @return Collection
     */
    public static function getMenuList($parentId = 0, $isUrl = false, Collection $permission = null)
    {
        is_int($parentId) && $parentId = "$parentId";
        !$permission && $permission = self::getPermissions();
        $menus = $permission->where('parent_id', $parentId)->sortByDesc('sort')->values();
        if ($isUrl) {
            $menus = $menus->filter(function($value, $key) {
                return !empty($value->url);
            });
        }
        foreach ($menus as $menu) {
            $menu['child'] = self::getMenuList($menu['id'], $isUrl, $permission);
        }
        return $menus;
    }

    /**
     * Get the current cached permissions.
     * @param array $params
     * @return Collection
     */
    protected static function getPermissions(array $params = []): Collection
    {
        return ApplicationContext::getContainer()
            ->get(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params);
    }
}
