<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicClassSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = Faculty::query()->updateOrCreate(
            ['code' => 'FST'],
            ['name' => 'Faculty of Science and Technology'],
        );

        $program = Program::query()->updateOrCreate(
            ['code' => 'BIT'],
            ['faculty_id' => $faculty->id, 'name' => 'Bachelor of Science in Information Technology'],
        );

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['name' => '2026-2027'],
            [
                'starts_at' => '2026-06-01',
                'ends_at' => '2027-05-31',
                'is_active' => true,
            ],
        );

        $semester = Semester::query()->updateOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'Semester 1'],
            ['starts_at' => '2026-06-01', 'ends_at' => '2026-11-30'],
        );

        $classGroup = ClassGroup::query()->updateOrCreate(
            ['semester_id' => $semester->id, 'code' => 'D1IT-D104-A'],
            [
                'program_id' => $program->id,
                'name' => 'Information Technology - Class A',
                'color' => 'violet',
                'status' => 'active',
            ],
        );

        $professor = User::query()->where('email', 'professor@gmail.com')->first();
        $student = User::query()->where('email', 'user@gmail.com')->first();

        if ($student !== null) {
            $classGroup->students()->syncWithoutDetaching([$student->id]);
        }

        $subjects = [
            ['code' => 'IT301', 'name' => 'Oracle Project', 'room' => 'Lab 301'],
            ['code' => 'IT302', 'name' => 'Client Server Application Development', 'room' => 'Lab 302'],
            ['code' => 'IT303', 'name' => 'Blockchain Technology', 'room' => 'Room 204'],
            ['code' => 'IT304', 'name' => 'Advanced PHP and MySQL', 'room' => 'Lab 304'],
            ['code' => 'IT305', 'name' => 'Network Administration', 'room' => 'Lab 305'],
            ['code' => 'IT306', 'name' => 'Network Design and Implementation', 'room' => 'Lab 306'],
        ];

        foreach ($subjects as $index => $subject) {
            $course = Course::query()->updateOrCreate(
                ['code' => $subject['code']],
                [
                    'program_id' => $program->id,
                    'name' => $subject['name'],
                    'description' => "Lessons, learning materials, and classwork for {$subject['name']}.",
                    'year_level' => 3,
                    'semester_number' => 1,
                    'credits' => 3,
                ],
            );

            $offering = CourseOffering::query()->updateOrCreate(
                ['class_group_id' => $classGroup->id, 'course_id' => $course->id],
                ['professor_id' => $professor?->id, 'room' => $subject['room']],
            );

            if ($index < 3) {
                Assignment::query()->updateOrCreate(
                    ['course_offering_id' => $offering->id, 'title' => $this->assignmentTitle($index)],
                    [
                        'created_by' => $professor?->id,
                        'instructions' => $this->assignmentInstructions($index),
                        'due_at' => now()->addDays(($index + 1) * 5)->setTime(23, 59),
                        'points' => 100,
                        'status' => 'published',
                    ],
                );
            }
        }
    }

    private function assignmentTitle(int $index): string
    {
        return [
            'Database Design Project',
            'Build a Client-Server Application',
            'Blockchain Fundamentals Exercise',
        ][$index];
    }

    private function assignmentInstructions(int $index): string
    {
        return [
            'Design the database schema and submit the SQL script with an ER diagram.',
            'Create a working client-server application and submit the source code repository.',
            'Explain the structure of a block and demonstrate a basic hash chain.',
        ][$index];
    }
}
