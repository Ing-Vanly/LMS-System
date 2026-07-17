<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create());
});

test('authenticated users can view role and permission management pages', function () {
    $this->get(route('roles.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles.data', 3)
            ->etc()
        );

    $this->get(route('roles.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/create')
            ->has('permissions', 17)
            ->etc()
        );

    $this->get(route('permissions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('permissions/index')
            ->has('permissions.data', 10)
            ->etc()
        );

    expect(Route::has('permissions.create'))->toBeFalse()
        ->and(Route::has('permissions.store'))->toBeFalse()
        ->and(Route::has('permissions.edit'))->toBeFalse()
        ->and(Route::has('permissions.update'))->toBeFalse()
        ->and(Route::has('permissions.destroy'))->toBeFalse();
});

test('roles can be created updated and deleted with permissions', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->post(route('roles.store'), [
        'name' => 'content-manager',
        'permissions' => ['reports.view', 'categories.view'],
    ])->assertRedirect(route('roles.index', absolute: false));

    $role = Role::findByName('content-manager');

    expect($role->hasAllPermissions(['reports.view', 'categories.view']))->toBeTrue();

    $this->get(route('roles.edit', $role))
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/edit')
            ->where('role.name', 'content-manager')
            ->where('role.permissions', ['categories.view', 'reports.view'])
            ->etc()
        );

    $this->put(route('roles.update', $role), [
        'name' => 'course-manager',
        'permissions' => ['reports.view'],
    ])->assertRedirect(route('roles.index', absolute: false));

    $role->refresh();

    expect($role->name)->toBe('course-manager')
        ->and($role->permissions()->pluck('name')->all())->toBe(['reports.view']);

    $this->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index', absolute: false));

    $this->assertModelMissing($role);
});

test('roles assigned to users cannot be deleted', function () {
    $role = Role::findByName('professor');
    $assignedUser = User::factory()->create();
    $assignedUser->assignRole($role);

    $this->delete(route('roles.destroy', $role))->assertRedirect();

    $this->assertModelExists($role);
});
