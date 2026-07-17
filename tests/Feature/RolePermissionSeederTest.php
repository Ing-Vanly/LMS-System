<?php

use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('role and permission seeder creates the expected access matrix', function () {
    Permission::create(['name' => 'permissions.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'permissions.update', 'guard_name' => 'web']);
    Permission::create(['name' => 'permissions.delete', 'guard_name' => 'web']);

    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'professor', 'user'])
        ->and(Permission::query()->count())->toBe(17)
        ->and(Permission::query()->where('name', 'permissions.create')->exists())->toBeFalse()
        ->and(Role::findByName('user')->hasPermissionTo('learning-materials.view'))->toBeTrue()
        ->and(Role::findByName('user')->hasPermissionTo('learning-materials.create'))->toBeFalse()
        ->and(Role::findByName('professor')->hasPermissionTo('learning-materials.create'))->toBeTrue()
        ->and(Role::findByName('professor')->hasPermissionTo('categories.view'))->toBeTrue()
        ->and(Role::findByName('admin')->permissions()->count())->toBe(17);
});
