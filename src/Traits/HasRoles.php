<?php

declare(strict_types = 1);

namespace Qingliu\Permission\Traits;

use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Collection;
use Qingliu\Permission\Contracts\Role;
use Hyperf\Database\Model\Builder;
use Qingliu\Permission\PermissionRegistrar;
use Hyperf\Database\Model\Relations\MorphToMany;
use Hyperf\Database\Model\Events\Deleting;

/**
 * Trait HasRoles
 * @property \Qingliu\Permission\Models\Role|Collection roles
 * @package Qingliu\Permission\Traits
 */
trait HasRoles
{

    use HasPermissions;

    private $roleClass;

    public function deleting(Deleting $event)
    {
        if (method_exists($this, 'isForceDeleting') && !$this->isForceDeleting()) {
            return;
        }
        $this->roles()->detach();
    }

    /**
     * @return \Qingliu\Permission\Models\Role
     */
    public function getRoleClass()
    {
        if (!isset($this->roleClass)) {
            $this->roleClass = ApplicationContext::getContainer()->get(PermissionRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model', config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        );
    }

    /**
     * Scope the model query to certain roles only.
     * @param Builder $query
     * @param $roles
     * @return Builder
     */
    public function scopeRole(Builder $query, $roles): Builder
    {
        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(function ($role) {
            if ($role instanceof Role) {
                return $role;
            }
            $method = is_numeric($role) ? 'findById' : 'findByName';
            return $this->getRoleClass()->{$method}($role);
        }, $roles);
        return $query->whereHas('roles', function (Builder $query) use ($roles) {
                    $query->where(function (Builder $query) use ($roles) {
                        foreach ($roles as $role) {
                            $query->orWhere(config('permission.table_names.roles') . '.id', $role->id);
                        }
                    });
                });
    }

    /**
     * @param mixed ...$roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
                ->flatten(1)
                ->map(function ($role) {
                    return empty($role) ? false : $this->getStoredRole($role);
                })->filter(function ($role) {
                    return $role instanceof Role;
                })->map->id->all();
        $model = $this->getModel();
        $this->roles()->sync($roles, false);
        $model->load('roles');
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param $role
     * @return $this
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));

        $this->load('roles');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param mixed ...$roles
     * @return HasRoles
     */
    public function syncRoles(...$roles)
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * @param $roles
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if (is_int($roles)) {
            return $this->roles->contains('id', "$roles");
        }

        if ($roles instanceof Role) {
            return $this->roles->contains('id', "{$roles->id}");
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }
        return $roles->intersect($this->roles)->isNotEmpty();
    }

    /**
     * @param $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * @param $roles
     * @return bool
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains('id', $roles->id);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($this->getRoleNames()) === $roles;
    }

    /**
     * Return all permissions directly coupled to the model.
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    protected function getStoredRole($role): Role
    {
        $roleClass = $this->getRoleClass();

        if (is_numeric($role)) {
            return $roleClass->findById($role);
        }

        if (is_string($role)) {
            return $roleClass->findByName($role);
        }

        return $role;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (!in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

}
