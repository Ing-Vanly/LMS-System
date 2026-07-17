<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authenticated users can manage login accounts', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager);

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/create')
            ->where('roles', ['user', 'professor', 'admin'])
            ->etc()
        );

    $this->post(route('users.store'), [
        'name' => 'Jane Manager',
        'email' => 'jane@example.com',
        'phone' => '012345678',
        'status' => 'active',
        'role' => 'professor',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('users.index', absolute: false));

    $created = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($created->phone)->toBe('012345678')
        ->and($created->status)->toBe('active')
        ->and($created->hasExactRoles('professor'))->toBeTrue()
        ->and($created->email_verified_at)->not->toBeNull();

    $this->get(route('users.edit', $created))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/edit')
            ->where('user.role', 'professor')
            ->where('roles', ['user', 'professor', 'admin'])
            ->etc()
        );

    $this->put(route('users.update', $created), [
        'name' => 'Jane Updated',
        'email' => 'jane.updated@example.com',
        'phone' => null,
        'status' => 'inactive',
        'role' => 'admin',
    ])->assertRedirect(route('users.index', absolute: false));

    $created->refresh();

    expect($created->name)->toBe('Jane Updated')
        ->and($created->email)->toBe('jane.updated@example.com')
        ->and($created->phone)->toBeNull()
        ->and($created->status)->toBe('inactive')
        ->and($created->hasExactRoles('admin'))->toBeTrue();

    $this->delete(route('users.destroy', $created))
        ->assertRedirect(route('users.index', absolute: false));

    $this->assertModelMissing($created);
});

test('users cannot delete their own account from user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->delete(route('users.destroy', $user))->assertRedirect();

    $this->assertModelExists($user);
});

test('users can upload an avatar for a login account', function () {
    Storage::fake('public');

    $manager = User::factory()->create();

    $this->actingAs($manager);

    $this->post(route('users.store'), [
        'name' => 'Avatar User',
        'email' => 'avatar@example.com',
        'phone' => null,
        'status' => 'active',
        'role' => 'user',
        'password' => 'password',
        'password_confirmation' => 'password',
        'avatar' => UploadedFile::fake()->image('avatar.jfif', 512, 512)->size(512),
    ])->assertRedirect(route('users.index', absolute: false));

    $created = User::query()->where('email', 'avatar@example.com')->firstOrFail();

    expect($created->avatar)->toStartWith('/storage/users/');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', $created->avatar));

    $indexUsers = collect($this->get(route('users.index'))->inertiaProps('users.data'));

    expect($indexUsers->firstWhere('email', 'avatar@example.com')['avatar'])->toBe($created->avatar);

    $this->get(route('users.edit', $created))
        ->assertInertia(fn (Assert $page) => $page
            ->where('user.avatar', $created->avatar)
            ->etc()
        );
});

test('users can upload an avatar to the public disk', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager);

    $this->post(route('users.store'), [
        'name' => 'Public Avatar User',
        'email' => 'public-avatar@example.com',
        'phone' => null,
        'status' => 'active',
        'role' => 'user',
        'password' => 'password',
        'password_confirmation' => 'password',
        'avatar' => UploadedFile::fake()->image('public-avatar.jpg', 512, 512)->size(512),
    ])->assertRedirect(route('users.index', absolute: false));

    $created = User::query()->where('email', 'public-avatar@example.com')->firstOrFail();
    $path = str_replace('/storage/', '', $created->avatar);

    expect($created->avatar)->toStartWith('/storage/users/');

    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->delete($path);
});

test('users can replace an avatar for a login account', function () {
    Storage::fake('public');

    $manager = User::factory()->create();
    $user = User::factory()->create([
        'avatar' => '/storage/avatars/old-avatar.jpg',
    ]);

    Storage::disk('public')->put('avatars/old-avatar.jpg', 'old image');

    $this->actingAs($manager);

    $this->post(route('users.update', $user), [
        '_method' => 'PUT',
        'name' => $user->name,
        'email' => $user->email,
        'phone' => null,
        'status' => 'active',
        'role' => 'professor',
        'avatar' => UploadedFile::fake()->image('new-avatar.jpg', 512, 512)->size(512),
    ])->assertRedirect(route('users.index', absolute: false));

    $user->refresh();
    $path = str_replace('/storage/', '', $user->avatar);

    expect($user->avatar)->toStartWith('/storage/users/')
        ->and($user->avatar)->not->toBe('/storage/avatars/old-avatar.jpg');

    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    Storage::disk('public')->assertExists($path);
});

test('unsupported avatars show a validation error', function () {
    Storage::fake('public');

    $manager = User::factory()->create();

    $this->actingAs($manager);

    $this->from(route('users.create'))->post(route('users.store'), [
        'name' => 'No Avatar User',
        'email' => 'no-avatar@example.com',
        'phone' => null,
        'status' => 'active',
        'role' => 'user',
        'password' => 'password',
        'password_confirmation' => 'password',
        'avatar' => UploadedFile::fake()->create('avatar.txt', 1, 'text/plain'),
    ])->assertRedirect(route('users.create', absolute: false))
        ->assertSessionHasErrors('avatar');

    expect(User::query()->where('email', 'no-avatar@example.com')->exists())->toBeFalse();
});
