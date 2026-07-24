<?php

use App\Models\Course;
use App\Models\Program;
use Database\Seeders\BusinessAdministrationCourseSeeder;
use Database\Seeders\FacultySeeder;
use Database\Seeders\ProgramSeeder;

test('business administration curricula are seeded without duplicates', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(BusinessAdministrationCourseSeeder::class);
    $this->seed(BusinessAdministrationCourseSeeder::class);

    $associatePrograms = ['ABA-ACC', 'ABA-FIN', 'ABA-MKT', 'ABA-EEM', 'ABA-HRMIR'];
    $bachelorPrograms = [
        'BA-GM',
        'BA-AF',
        'BA-FB',
        'BA-MKT',
        'BA-LPM',
        'BBA-MKT',
        'BBA-HRMIR',
        'BBA-EEM',
        'BBA-ACC',
        'BBA-FIN',
    ];
    $programs = Program::query()
        ->whereIn('code', [...$associatePrograms, ...$bachelorPrograms])
        ->withCount('courses')
        ->get()
        ->keyBy('code');

    expect($programs)->toHaveCount(15)
        ->and(Course::query()->whereIn('program_id', $programs->pluck('id'))->count())->toBe(490);

    foreach ($associatePrograms as $programCode) {
        expect($programs->get($programCode)?->courses_count)->toBe(20);
    }

    foreach ($bachelorPrograms as $programCode) {
        expect($programs->get($programCode)?->courses_count)->toBe(39);
    }

    expect(Course::query()
        ->whereIn('program_id', $programs->pluck('id'))
        ->where('code', 'CST058')
        ->count())->toBe(15);
});

test('every seeded business administration semester carries fifteen credits', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(BusinessAdministrationCourseSeeder::class);

    $programs = Program::query()
        ->whereIn('code', [
            'ABA-ACC',
            'ABA-FIN',
            'ABA-MKT',
            'ABA-EEM',
            'ABA-HRMIR',
            'BA-GM',
            'BA-AF',
            'BA-FB',
            'BA-MKT',
            'BA-LPM',
            'BBA-MKT',
            'BBA-HRMIR',
            'BBA-EEM',
            'BBA-ACC',
            'BBA-FIN',
        ])
        ->get();

    foreach ($programs as $program) {
        $expectedYears = str_starts_with($program->code, 'ABA-') ? 2 : 4;
        $semesterCredits = $program->courses()
            ->selectRaw('year_level, semester_number, sum(credits) as total_credits')
            ->groupBy('year_level', 'semester_number')
            ->get();

        expect($semesterCredits)->toHaveCount($expectedYears * 2);

        foreach ($semesterCredits as $semester) {
            expect((int) $semester->getAttribute('total_credits'))
                ->toBe(15, "{$program->code} year {$semester->year_level}, semester {$semester->semester_number}");
        }
    }
});

test('professional program names and representative courses match the supplied curricula', function () {
    $this->seed(FacultySeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(BusinessAdministrationCourseSeeder::class);

    expect(Program::query()->where('code', 'ABA-EEM')->value('name'))
        ->toBe('Associate of Business Administration in Entrepreneurship and Enterprise Management (Professional)')
        ->and(Program::query()->where('code', 'BBA-ACC')->value('name'))
        ->toBe('Bachelor of Business Administration in Accounting (Professional)')
        ->and(Program::query()->where('code', 'BBA-FIN')->value('name'))
        ->toBe('Bachelor of Business Administration in Finance (Professional)');

    $accounting = Program::query()->where('code', 'ABA-ACC')->firstOrFail();
    $logistics = Program::query()->where('code', 'BA-LPM')->firstOrFail();
    $professionalFinance = Program::query()->where('code', 'BBA-FIN')->firstOrFail();

    expect($accounting->courses()
        ->where('code', 'BUS031')
        ->where('year_level', 2)
        ->where('semester_number', 2)
        ->value('name'))->toBe('Internship Project Paper for Accounting')
        ->and($logistics->courses()
            ->where('code', 'MGT221')
            ->where('year_level', 4)
            ->where('semester_number', 2)
            ->value('credits'))->toBe(6)
        ->and($professionalFinance->courses()
            ->where('code', 'RPJ216')
            ->value('name'))->toBe('Research Project for Finance');
});
