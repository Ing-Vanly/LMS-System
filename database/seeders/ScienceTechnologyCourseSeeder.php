<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ScienceTechnologyCourseSeeder extends Seeder
{
    public function run(): void
    {
        $curricula = $this->curricula();
        $programs = Program::query()
            ->whereIn('code', array_keys($curricula))
            ->get()
            ->keyBy('code');

        foreach ($curricula as $programCode => $courses) {
            $program = $programs->get($programCode);

            if (! $program instanceof Program) {
                throw new \RuntimeException("Program {$programCode} must be seeded before its courses.");
            }

            foreach ($courses as $course) {
                Course::query()->updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'code' => $course['code'],
                        'year_level' => $course['year_level'],
                        'semester_number' => $course['semester_number'],
                    ],
                    [
                        'name' => $course['name'],
                        'credits' => $course['credits'],
                        'description' => null,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, list<array{code: string, name: string, year_level: int, semester_number: int, credits: int}>>
     */
    private function curricula(): array
    {
        $firstYearFirstSemester = $this->term(1, 1, [
            ['ENG003', 'Core English'],
            ['CST068', 'Digital Literacy'],
            ['FIN089', 'Financial Literacy'],
            ['GED003', 'Critical Thinking and Growth Mindset'],
            ['ECO046', 'Economics'],
        ]);

        return [
            'BIT' => [
                ...$firstYearFirstSemester,
                ...$this->term(1, 2, [
                    ['ENG041', 'Presentation Skills'],
                    ['CST006', 'Graphic Design'],
                    ['CST003', 'Mathematics for Computing'],
                    ['CST299', 'IT Support'],
                    ['CST300', 'Python Programming'],
                ]),
                ...$this->term(2, 1, [
                    ['CST309', 'Front-End Development'],
                    ['CST056', 'Python Project'],
                    ['CST055', 'Computer Network'],
                    ['CST119', 'Database Design and SQL Server'],
                    ['CTS298', 'Data Structures & Algorithms'],
                ]),
                ...$this->term(2, 2, [
                    ['CST310', 'Back-End Development'],
                    ['CST018', 'Personal Application Development'],
                    ['CST016', 'Computer System Administration'],
                    ['DMD009', 'UX/UI Design'],
                    ['CST050', 'OOP Using Java'],
                ]),
                ...$this->term(3, 1, [
                    ['CST304', 'Full-Stack Developer'],
                    ['CST023', 'Client/Server Application Development'],
                    ['CST301', 'Java Project'],
                    ['CST049', 'Network Design and Implementation'],
                    ['CST305', 'Operating System with Linux'],
                ]),
                ...$this->term(3, 2, [
                    ['CST012', 'Web Application Development'],
                    ['CST306', 'Software Engineering and System Analysis'],
                    ['CST026', 'Mobile Programming I'],
                    ['CST308', 'Internetworking'],
                    ['CST037', 'Network Administration in Linux'],
                ]),
                ...$this->term(4, 1, [
                    ['CST307', 'Cloud Computing and DevOps'],
                    ['CST121', 'Oracle Database'],
                    ['CST302', 'Cybersecurity'],
                    ['CST031', 'Mobile Programming II'],
                    ['CST071', 'Research Methodology'],
                ]),
                ...$this->term(4, 2, [
                    ['CST052', 'Blockchain Technology'],
                    ['CST027', 'Oracle Project'],
                    ['CST118', 'AI and Machine Learning'],
                    ['CST115', 'Research Project for Science', 6],
                ]),
            ],
            'BDMD' => [
                ...$firstYearFirstSemester,
                ...$this->term(1, 2, [
                    ['ENG041', 'Presentation Skills'],
                    ['DMD036', 'Photography'],
                    ['CST124', 'Contemporary AI Issue'],
                    ['DMD037', 'Digital Graphic'],
                    ['DMD050', 'Drawing and Visual Communication'],
                ]),
                ...$this->term(2, 1, [
                    ['DMD003', 'Design and Publishing'],
                    ['DMD028', 'Advanced Photography'],
                    ['DMD029', 'Storyboarding'],
                    ['DMD051', 'AI for Creative Media'],
                    ['DMD004', 'Video and Audio Editing I'],
                ]),
                ...$this->term(2, 2, [
                    ['DMD019', 'Content Writing'],
                    ['DMD052', 'Lighting Design and Studio Practice'],
                    ['DMD026', '2D Animation Design'],
                    ['DMD009', 'UX/UI Design'],
                    ['DMD005', 'Video and Audio Editing II'],
                ]),
                ...$this->term(3, 1, [
                    ['MKT917', 'Digital Marketing'],
                    ['DMD008', '3D Modeling and Animation I'],
                    ['DMD044', 'Motion Graphics I'],
                    ['DMD030', 'Digital Storytelling'],
                    ['DMD013', 'Video Production I'],
                ]),
                ...$this->term(3, 2, [
                    ['DMD027', 'Social Media Management'],
                    ['DMD011', '3D Modeling and Animation II'],
                    ['DMD045', 'Motion Graphics II'],
                    ['DMD016', 'Digital Media Production'],
                    ['DMD015', 'Video Production II'],
                ]),
                ...$this->term(4, 1, [
                    ['DMD018', 'Cinema 4D'],
                    ['DMD033', 'Creative Advertising'],
                    ['DMD046', 'Advance Video Production & Post Production'],
                    ['DMD047', 'Content Strategy & Analytics'],
                    ['CST071', 'Research Methodology'],
                ]),
                ...$this->term(4, 2, [
                    ['DMD032', '3D Visual Effects'],
                    ['DMD048', 'Digital Media Ethic and Copyright'],
                    ['DMD049', 'Digital Media Project Management'],
                    ['DMD020', 'Research Project for Digital Media Design', 6],
                ]),
            ],
        ];
    }

    /**
     * @param  list<array{0: string, 1: string, 2?: int}>  $courses
     * @return list<array{code: string, name: string, year_level: int, semester_number: int, credits: int}>
     */
    private function term(int $year, int $semester, array $courses): array
    {
        return array_map(
            fn (array $course): array => [
                'code' => $course[0],
                'name' => $course[1],
                'year_level' => $year,
                'semester_number' => $semester,
                'credits' => $course[2] ?? 3,
            ],
            $courses,
        );
    }
}
