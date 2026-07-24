<?php

use App\Models\Course;
use App\Models\Program;
use Database\Seeders\FacultySeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\ScienceTechnologyCourseSeeder;

test('science and technology curricula are seeded without duplicates', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(ScienceTechnologyCourseSeeder::class);
    $this->seed(ScienceTechnologyCourseSeeder::class);

    $programs = Program::query()
        ->whereIn('code', ['BIT', 'BDMD'])
        ->withCount('courses')
        ->get()
        ->keyBy('code');

    expect($programs)->toHaveCount(2)
        ->and($programs->get('BIT')?->courses_count)->toBe(39)
        ->and($programs->get('BDMD')?->courses_count)->toBe(39)
        ->and(Course::query()->whereIn('program_id', $programs->pluck('id'))->count())->toBe(78);
});

test('every seeded science and technology semester carries fifteen credits', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(ScienceTechnologyCourseSeeder::class);

    $programs = Program::query()
        ->whereIn('code', ['BIT', 'BDMD'])
        ->get();

    foreach ($programs as $program) {
        $semesterCredits = $program->courses()
            ->selectRaw('year_level, semester_number, sum(credits) as total_credits')
            ->groupBy('year_level', 'semester_number')
            ->get();

        expect($semesterCredits)->toHaveCount(8);

        foreach ($semesterCredits as $semester) {
            expect((int) $semester->getAttribute('total_credits'))
                ->toBe(15, "{$program->code} year {$semester->year_level}, semester {$semester->semester_number}");
        }
    }
});

test('science and technology representative courses match the supplied curricula', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(ScienceTechnologyCourseSeeder::class);

    $informationTechnology = Program::query()->where('code', 'BIT')->firstOrFail();
    $digitalMediaDesign = Program::query()->where('code', 'BDMD')->firstOrFail();

    expect($informationTechnology->name)->toBe('Bachelor of Science in Information Technology')
        ->and($informationTechnology->courses()
            ->where('code', 'CTS298')
            ->where('year_level', 2)
            ->where('semester_number', 1)
            ->value('name'))->toBe('Data Structures & Algorithms')
        ->and($informationTechnology->courses()
            ->where('code', 'CST115')
            ->value('credits'))->toBe(6)
        ->and($digitalMediaDesign->name)->toBe('Bachelor of Science in Digital Media Design')
        ->and($digitalMediaDesign->courses()
            ->where('code', 'DMD046')
            ->value('name'))->toBe('Advance Video Production & Post Production')
        ->and($digitalMediaDesign->courses()
            ->where('code', 'DMD020')
            ->value('credits'))->toBe(6);
});
