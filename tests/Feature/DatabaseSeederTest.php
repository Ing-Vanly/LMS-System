<?php

use App\Models\Assignment;
use App\Models\ClassGroup;
use App\Models\CourseOffering;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

test('the default database seeder does not create sample classes', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ClassGroup::query()->count())->toBe(0)
        ->and(CourseOffering::query()->count())->toBe(0)
        ->and(Assignment::query()->count())->toBe(0)
        ->and(DB::table('class_enrollments')->count())->toBe(0);
});
