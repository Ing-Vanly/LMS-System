<?php

use App\Models\ClassGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicClassFixture;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    AcademicClassFixture::create();
});

test('students can view their enrolled classes and class workspace', function () {
    $student = User::query()->where('email', 'user@gmail.com')->firstOrFail();
    $classGroup = ClassGroup::query()->where('code', 'D1IT-D104-A')->firstOrFail();

    $this->actingAs($student)
        ->get(route('classes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('classes/index')
            ->has('classes', 1)
            ->where('classes.0.code', 'D1IT-D104-A')
            ->where('classes.0.subjects_count', 6)
            ->etc()
        );

    $this->actingAs($student)
        ->get(route('classes.show', $classGroup))
        ->assertInertia(fn (Assert $page) => $page
            ->component('classes/show')
            ->where('classroom.code', 'D1IT-D104-A')
            ->has('classroom.subjects', 6)
            ->where('classroom.subjects.0.name', 'Oracle Project')
            ->has('classroom.subjects.0.assignments', 1)
            ->etc()
        );
});

test('professors can view classes containing their teaching assignments', function () {
    $professor = User::query()->where('email', 'professor@gmail.com')->firstOrFail();

    $this->actingAs($professor)
        ->get(route('classes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('classes/index')
            ->has('classes', 1)
            ->where('classes.0.code', 'D1IT-D104-A')
            ->etc()
        );
});

test('users cannot open a class unless they are enrolled or teaching it', function () {
    $classGroup = ClassGroup::query()->where('code', 'D1IT-D104-A')->firstOrFail();
    $unassignedUser = User::factory()->create();
    $unassignedUser->assignRole('user');

    $this->actingAs($unassignedUser)
        ->get(route('classes.show', $classGroup))
        ->assertNotFound();
});
