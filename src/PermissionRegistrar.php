<?php

namespace Qingliu\Permission;

use Hyperf\Utils\Collection;
use Qingliu\Permission\Contracts\Role;
use Qingliu\Permission\Contracts\Permission;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class PermissionRegistrar {

    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    /** @var string */
    protected $permissionClass;

    /** @var string */
    protected $roleClass;

    /** @var \Hyperf\Utils\Collection */
    protected $permissions;

    /** @var \DateInterval|int */
    public static $cacheExpirationTime;

    /** @var string */
    public static $cacheKey;

    /** @var string */
    public static $cacheModelKey;

    public function __construct(ContainerInterface $container, CacheInterface $cache) {

        $this->container            = $container;
        $this->cache                = $cache;

        $this->permissionClass      = config('permission.models.permission');
        $this->roleClass            = config('permission.models.role');

        self::$cacheExpirationTime  = config('permission.cache.expiration_time', config('permission.cache_expiration_time'));
        self::$cacheKey             = config('permission.cache.key');
        self::$cacheModelKey        = config('permission.cache.model_key');
    }

    /**
     * Flush the cache.
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function forgetCachedPermissions() {
        $this->permissions = null;

        return $this->cache->delete(self::$cacheKey);
    }

    /**
     * @param array $params
     * @return Collection
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getPermissions(array $params = []): Collection {
        if ($this->permissions === null) {
            if ($this->cache->has(self::$cacheKey)) {
                $this->permissions = $this->cache->get(self::$cacheKey);
            } else {
                $this->permissions = $this->getPermissionClass()
                        ->with('roles')
                        ->get();
                $this->cache->set(self::$cacheKey, $this->permissions, self::$cacheExpirationTime);
            }
        }

        $permissions = clone $this->permissions;

        foreach ($params as $attr => $value) {
            $permissions = $permissions->where($attr, $value);
        }

        return $permissions;
    }

    /**
     * Get an instance of the permission class.
     * @return Permission
     */
    public function getPermissionClass(): Permission {
        return $this->container->get($this->permissionClass);
    }

    public function setPermissionClass($permissionClass) {
        $this->permissionClass = $permissionClass;

        return $this;
    }

    /**
     * Get an instance of the role class.
     * @return Role
     */
    public function getRoleClass(): Role {
        return $this->container->get($this->roleClass);
    }
}
