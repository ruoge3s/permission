# 抄个权限管理用

 - 命令行只保留清除缓存
   ```
   php bin/hyperf.php permission:cache-reset
   ```
- roles增加description字段,非必填

## 安装

 ```
  composer require ruoge3s/permission
 ```
发布配置
```
 php bin/hyperf.php vendor:publish ruoge3s/permission
```
修改配置文件config/autoload/permission.php

数据库迁移

```
php bin/hyperf.php migrate
```
将Qingliu\Permission\Traits\HasRoles添加到你的用户Model

```
...
use Qingliu\Permission\Traits\HasRoles;

class User extends Model {
    
    use HasRoles;
   ...
}
```
## 使用

```
use Qingliu\Permission\Models\Permission;
use Qingliu\Permission\Models\Role;

//创建一个角色
$role = Role::create(['name' => '管理员','description'=>'']);
//创建权限
$permission = Permission::create(['name' => 'user-center/user/get','display_name'=>'用户管理','url'=>'user-center/user']);
$permission = Permission::create(['name' => 'user-center/user/post','display_name'=>'创建用户','parent_id'=>$p1->id]);
//为角色分配一个权限
$role->givePermissionTo($permission);
$role->syncPermissions($permissions);//多个
$role->permissions()->sync([1,2,3]);
//权限添加到一个角色
$permission->assignRole($role);
$permission->syncRoles($roles);//多个
$permission->roles()->sync([1,2,3]);
//删除权限
$role->revokePermissionTo($permission);
$permission->removeRole($role);
//为用户直接分配权限
$user->givePermissionTo('user-center/user/get');
//为用户分配角色
$user->assignRole('管理员');
$user->assignRole($role->id);
$user->assignRole($role);
$user->assignRole(['管理员', '普通用户']);
$user->roles()->sync([1,2,3]);
//删除角色
$user->removeRole('管理员');
//获取角色集合
$user->getRoleNames();
//获取所有权限
$user->getAllPermissions();

//验证
$user->can('user-center/user/get');
$user->can($permission->id);
$user->can($permission);
$user->hasAnyPermission([$permission1,$permission2]);
$user->hasAnyPermission(['user-center/user/get','user-center/user/post']);
$user->hasAnyPermission([1,2]);
$user->hasRole('管理员');
$user->hasRole(['管理员','普通用户']);
$user->hasRole($role);
$user->hasRole([$role1,$role2]);
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
