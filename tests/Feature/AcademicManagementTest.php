<?php

use App\Models\Assignment;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::query()->where('email', 'admin@gmail.com')->firstOrFail());
});

test('academic master data pages are available', function (string $resource) {
    $this->get(route('academic.index', ['resource' => $resource]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('academic/index')
            ->where('resource', $resource)
            ->has('records.data')
            ->has('fields')
            ->etc()
        );
})->with(['faculties', 'academic-years', 'semesters', 'courses']);

test('programs are grouped by faculty and managed by the seeder', function () {
    $this->get(route('academic.index', ['resource' => 'programs']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('academic/programs/index')
            ->has('faculties.data', 8)
            ->etc()
        );

    $faculty = Faculty::query()->where('code', 'FST')->firstOrFail();

    $this->get(route('academic.programs.faculties.show', $faculty))
        ->assertInertia(fn (Assert $page) => $page
            ->component('academic/programs/show')
            ->where('faculty.code', 'FST')
            ->has('faculty.programs', 8)
            ->has('faculty.programs.0.courses')
            ->etc()
        );

    $this->post(route('academic.store', ['resource' => 'programs']), [
        'name' => 'Manual Program',
        'code' => 'MANUAL',
        'faculty_id' => Faculty::query()->firstOrFail()->id,
    ])->assertRedirect();

    expect(Program::query()->where('code', 'MANUAL')->firstOrFail()->faculty_id)
        ->toBe(Faculty::query()->firstOrFail()->id);
});

test('academic master data create and edit forms use list page modals', function () {
    expect(Route::has('academic.create'))->toBeFalse()
        ->and(Route::has('academic.edit'))->toBeFalse();
});

test('faculties can be created updated and deleted', function () {
    $this->post(route('academic.store', ['resource' => 'faculties']), [
        'name' => 'Faculty of Business',
        'code' => 'FOB',
    ])->assertRedirect(route('academic.index', ['resource' => 'faculties'], absolute: false));

    $faculty = Faculty::query()->where('code', 'FOB')->firstOrFail();

    $this->put(route('academic.update', ['resource' => 'faculties', 'record' => $faculty]), [
        'name' => 'Faculty of Business and Economics',
        'code' => 'FBE',
    ])->assertRedirect();

    expect($faculty->refresh()->name)->toBe('Faculty of Business and Economics');

    $this->delete(route('academic.destroy', ['resource' => 'faculties', 'record' => $faculty]))
        ->assertRedirect();
    $this->assertModelMissing($faculty);
});

test('courses can be created directly inside a faculty program', function () {
    $program = Program::query()->where('code', 'ADMD')->firstOrFail();

    $this->post(route('academic.programs.courses.store', $program), [
        'name' => 'Digital Design Fundamentals',
        'code' => 'DMD101',
        'credits' => 3,
        'year_level' => 2,
        'semester_number' => 1,
        'description' => 'Introduction to digital design.',
    ])->assertRedirect();

    $course = Course::query()->where('code', 'DMD101')->firstOrFail();

    expect($course->program_id)->toBe($program->id)
        ->and($course->name)->toBe('Digital Design Fundamentals')
        ->and($course->year_level)->toBe(2)
        ->and($course->semester_number)->toBe(1);

    $this->put(route('academic.programs.courses.update', [$program, $course]), [
        'name' => 'Digital Design Principles',
        'code' => 'DMD101',
        'credits' => 4,
        'year_level' => 5,
        'semester_number' => 2,
        'description' => 'Updated digital design course.',
    ])->assertRedirect();

    expect($course->refresh()->name)->toBe('Digital Design Principles')
        ->and($course->credits)->toBe(4)
        ->and($course->year_level)->toBe(5)
        ->and($course->semester_number)->toBe(2);

    $this->delete(route('academic.programs.courses.destroy', [$program, $course]))
        ->assertRedirect();
    $this->assertModelMissing($course);
});

test('classes can be created with subject assignments and enrolled students', function () {
    $program = Program::query()->where('code', 'BIT')->firstOrFail();
    $semester = Semester::query()->firstOrFail();
    $course = Course::query()->where('program_id', $program->id)->firstOrFail();
    $professor = User::query()->where('email', 'professor@gmail.com')->firstOrFail();
    $student = User::query()->where('email', 'user@gmail.com')->firstOrFail();

    $this->post(route('academic.classes.store'), [
        'name' => 'Information Technology - Class B',
        'code' => 'D1IT-D104-B',
        'program_id' => $program->id,
        'semester_id' => $semester->id,
        'color' => 'blue',
        'status' => 'active',
        'offerings' => [[
            'course_id' => $course->id,
            'professor_id' => $professor->id,
            'room' => 'Lab 401',
        ]],
        'student_ids' => [$student->id],
    ])->assertRedirect(route('academic.classes.index', absolute: false));

    $classGroup = ClassGroup::query()->where('code', 'D1IT-D104-B')->firstOrFail();

    expect($classGroup->courseOfferings()->count())->toBe(1)
        ->and($classGroup->students()->whereKey($student)->exists())->toBeTrue();

    $this->get(route('academic.classes.edit', $classGroup))
        ->assertInertia(fn (Assert $page) => $page
            ->component('academic/classes/form')
            ->where('classroom.code', 'D1IT-D104-B')
            ->etc()
        );

    $offering = $classGroup->courseOfferings()->firstOrFail();
    $this->put(route('academic.classes.update', $classGroup), [
        'name' => 'Information Technology - Updated Class B',
        'code' => 'D1IT-D104-B',
        'program_id' => $program->id,
        'semester_id' => $semester->id,
        'color' => 'emerald',
        'status' => 'active',
        'offerings' => [[
            'id' => $offering->id,
            'course_id' => $course->id,
            'professor_id' => $professor->id,
            'room' => 'Lab 402',
        ]],
        'student_ids' => [$student->id],
    ])->assertRedirect();

    expect($classGroup->refresh()->name)->toBe('Information Technology - Updated Class B')
        ->and($offering->refresh()->room)->toBe('Lab 402');

    $this->delete(route('academic.classes.destroy', $classGroup))->assertRedirect();
    $this->assertModelMissing($classGroup);
});

test('assigned professors can create update and delete assignments', function () {
    $classGroup = ClassGroup::query()->where('code', 'D1IT-D104-A')->firstOrFail();
    $offering = $classGroup->courseOfferings()->firstOrFail();
    $professor = User::query()->where('email', 'professor@gmail.com')->firstOrFail();
    $this->actingAs($professor);

    $this->post(route('academic.assignments.store', [$classGroup, $offering]), [
        'title' => 'New practical exercise',
        'instructions' => 'Submit the completed source code.',
        'due_at' => '2026-08-01 23:59:00',
        'points' => 50,
        'status' => 'published',
    ])->assertRedirect(route('classes.show', $classGroup, absolute: false));

    $assignment = Assignment::query()->where('title', 'New practical exercise')->firstOrFail();

    $this->put(route('academic.assignments.update', [$classGroup, $offering, $assignment]), [
        'title' => 'Updated practical exercise',
        'instructions' => 'Submit source code and documentation.',
        'due_at' => null,
        'points' => 75,
        'status' => 'draft',
    ])->assertRedirect();

    expect($assignment->refresh()->points)->toBe(75);

    $this->delete(route('academic.assignments.destroy', [$classGroup, $offering, $assignment]))
        ->assertRedirect();
    $this->assertModelMissing($assignment);
});
