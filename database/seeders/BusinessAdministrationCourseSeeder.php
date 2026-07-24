<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class BusinessAdministrationCourseSeeder extends Seeder
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
        $associateCommon = [
            ...$this->term(1, 1, [
                ['CST058', 'Digital Literacy'],
                ['ECO046', 'Economics I'],
                ['TQ4', 'Introduction to Business'],
                ['FY101', 'Core English'],
                ['MGT156', 'Principles of Management'],
            ]),
            ...$this->term(1, 2, [
                ['CST124', 'Contemporary AI Issues'],
                ['BUS030', 'Basic Statistics for Business and Economics'],
                ['BM30', 'Money and Banking'],
                ['FY102', 'English for Academic Writing'],
                ['MKT166', 'Basic Marketing'],
            ]),
        ];

        $associateAccounting = [
            ...$this->term(2, 1, [
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['MGT106', 'Human Resource Management I'],
                ['MKT168', 'Marketing Management I'],
                ['CST164', 'Digital Business'],
                ['ENG601', 'English for Business'],
            ]),
            ...$this->term(2, 2, [
                ['TQ1B', 'Bookkeeping, Controls, and Accounting II'],
                ['TQ3', 'Introduction to Costing'],
                ['TQ7', 'Cambodian Taxation and Practices'],
                ['FIN080', 'Finance'],
                ['BUS031', 'Internship Project Paper for Accounting'],
            ]),
        ];

        $associateFinance = [
            ...$this->term(2, 1, [
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['MGT106', 'Human Resource Management I'],
                ['MKT168', 'Marketing Management I'],
                ['CST164', 'Digital Business'],
                ['ENG601', 'English for Business'],
            ]),
            ...$this->term(2, 2, [
                ['TQ8', 'Cambodian Business and Company Law'],
                ['FIN080', 'Finance'],
                ['TQ7', 'Cambodian Taxation and Practices'],
                ['FIN052', 'Corporate Finance'],
                ['BUS031', 'Internship Project Paper for Finance'],
            ]),
        ];

        $associateMarketing = [
            ...$this->term(2, 1, [
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['ACC086', 'Human Resource Management I'],
                ['MKT168', 'Marketing Management I'],
                ['CST164', 'Digital Business'],
                ['ENG601', 'English for Business'],
            ]),
            ...$this->term(2, 2, [
                ['MKT040', 'Consumer Behavior'],
                ['PSY001', 'Personal Selling'],
                ['MKT169', 'Marketing Management II'],
                ['MKT917', 'Digital Marketing'],
                ['BUS031', 'Internship Project Paper for Marketing'],
            ]),
        ];

        $associateEntrepreneurship = [
            ...$this->term(2, 1, [
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['MGT106', 'Human Resource Management I'],
                ['MKT168', 'Marketing Management I'],
                ['CST164', 'Digital Business'],
                ['ENG601', 'English for Business'],
            ]),
            ...$this->term(2, 2, [
                ['MGT123', 'Entrepreneurship Management'],
                ['ECO124', 'Economic Analysis for Enterprises'],
                ['MGT126', 'Small Business Management'],
                ['MGT270', 'Work and Organizational Behavior'],
                ['BUS031', 'Internship Project Paper for Entrepreneurship and Enterprise Management'],
            ]),
        ];

        $associateHumanResources = [
            ...$this->term(2, 1, [
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['MGT106', 'Human Resource Management I'],
                ['MKT168', 'Marketing Management I'],
                ['CST164', 'Digital Business'],
                ['ENG601', 'English for Business'],
            ]),
            ...$this->term(2, 2, [
                ['MGT123', 'Entrepreneurship Management'],
                ['ECO124', 'Economic Analysis for Enterprises'],
                ['MGT126', 'Small Business Management'],
                ['MGT270', 'Work and Organizational Behavior'],
                ['BUS031', 'Internship Project Paper for Human Resource Management and Industrial Relations'],
            ]),
        ];

        $bachelorCommon = [
            ...$this->term(1, 1, [
                ['CST058', 'Digital Literacy'],
                ['FY004', 'Critical Thinking and Growth Mindset'],
                ['FY101', 'Core English'],
                ['ECO046', 'Economics I'],
                ['FIN099', 'Financial Literacy'],
            ]),
            ...$this->term(1, 2, [
                ['CST124', 'Contemporary AI Issues'],
                ['TQ4', 'Introduction to Business'],
                ['FY102', 'English for Academic Writing'],
                ['ECO047', 'Economics II'],
                ['BUS030', 'Basic Statistics for Business and Economics'],
            ]),
            ...$this->term(2, 1, [
                ['ENG601', 'English for Business'],
                ['TQ1A', 'Bookkeeping, Controls, and Accounting I'],
                ['MKT166', 'Basic Marketing'],
                ['MGT156', 'Principles of Management'],
                ['CST164', 'Digital Business'],
            ]),
            ...$this->term(2, 2, [
                ['MGT271', 'Innovation and Entrepreneurship'],
                ['TQ1B', 'Bookkeeping, Controls, and Accounting II'],
                ['MKT173', 'Marketing Research'],
                ['MGT270', 'Work and Organizational Behavior'],
                ['FIN080', 'Finance'],
            ]),
        ];

        return [
            'ABA-ACC' => [...$associateCommon, ...$associateAccounting],
            'ABA-FIN' => [...$associateCommon, ...$associateFinance],
            'ABA-MKT' => [...$associateCommon, ...$associateMarketing],
            'ABA-EEM' => [...$associateCommon, ...$associateEntrepreneurship],
            'ABA-HRMIR' => [...$associateCommon, ...$associateHumanResources],
            'BA-GM' => [
                ...$bachelorCommon,
                ...$this->term(3, 1, [
                    ['MGT106', 'Human Resource Management I'],
                    ['MKT168', 'Marketing Management I'],
                    ['FIN095', 'Financial Management I'],
                    ['TQ3', 'Introduction to Costing'],
                    ['MGT826', 'Generative AI for Business'],
                ]),
                ...$this->term(3, 2, [
                    ['MGT107', 'Human Resource Management II'],
                    ['MGT179', 'Operations Management'],
                    ['CST600', 'Management Information Systems'],
                    ['MGT189', 'Project Management'],
                    ['MGT962', 'Small & Medium Enterprise (SMEs) Management'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT825', 'Business Intelligence'],
                    ['MGT237', 'Strategic Management I'],
                    ['MGT246', 'Total Quality Management I'],
                    ['MGT952', 'Business Ethics'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['MGT135', 'International Management'],
                    ['MGT238', 'Strategic Management II'],
                    ['MGT272', 'Managing Organizational Change'],
                    ['MGT220', 'Research Project for Management', 6],
                ]),
            ],
            'BA-AF' => [
                ...$bachelorCommon,
                ...$this->term(3, 1, [
                    ['TQ7', 'Cambodian Taxation and Practices'],
                    ['TQ6A', 'Management Accounting I'],
                    ['ACC019', 'Auditing and Assurance Principles'],
                    ['TQ5A', 'Financial Statement Preparation I'],
                    ['TQ3', 'Introduction to Costing'],
                ]),
                ...$this->term(3, 2, [
                    ['FIN052', 'Corporate Finance I'],
                    ['TQ6B', 'Management Accounting II'],
                    ['TQ2', 'IT Skill and Software'],
                    ['TQ5B', 'Financial Statement Preparation II'],
                    ['TQ8', 'Cambodian Business and Company Law'],
                ]),
                ...$this->term(4, 1, [
                    ['ACC261', 'International Financial Reporting Standards'],
                    ['FIN095', 'Financial Management I'],
                    ['MGT825', 'Business Intelligence'],
                    ['MGT952', 'Business Ethics'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['ACC263', 'International Financial Reporting Standard for SMEs and NGOs'],
                    ['FIN096', 'Financial Management II'],
                    ['FIN143', 'Investment Management'],
                    ['ACC209', 'Research Project for Accounting and Finance', 6],
                ]),
            ],
            'BA-FB' => [
                ...$bachelorCommon,
                ...$this->term(3, 1, [
                    ['FIN057', 'Credit and Lending Decisions I'],
                    ['FIN095', 'Financial Management I'],
                    ['FIN052', 'Corporate Finance I'],
                    ['FIN128', 'International Finance'],
                    ['MGT826', 'Generative AI for Business'],
                ]),
                ...$this->term(3, 2, [
                    ['FIN058', 'Credit and Lending Decisions II'],
                    ['FIN096', 'Financial Management II'],
                    ['FIN053', 'Corporate Finance II'],
                    ['FIN217', 'Financial Law and Regulations'],
                    ['TQ8', 'Cambodian Business and Company Law'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT825', 'Business Intelligence'],
                    ['FIN093', 'Financial Institutions Management I'],
                    ['FIN145', 'Portfolio Management'],
                    ['MGT952', 'Business Ethics'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['FIN143', 'Investment Management'],
                    ['FIN093', 'Financial Institutions Management II'],
                    ['FIN933', 'Risk Management and Insurance'],
                    ['FIN213', 'Research Project for Finance and Banking', 6],
                ]),
            ],
            'BA-MKT' => [
                ...$bachelorCommon,
                ...$this->term(3, 1, [
                    ['MKT165', 'Marketing for Services'],
                    ['MKT119', 'Integrated Marketing Communications'],
                    ['MKT168', 'Marketing Management I'],
                    ['MGT826', 'Generative AI for Business'],
                    ['MKT040', 'Consumer Behavior'],
                ]),
                ...$this->term(3, 2, [
                    ['MKT825', 'Social Media Management'],
                    ['MKT826', 'Digital Media Production'],
                    ['MKT169', 'Marketing Management II'],
                    ['MKT936', 'Business to Business (B2B) Marketing'],
                    ['MKT177', 'New Product and Services Innovations'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT952', 'Business Ethics'],
                    ['MKT827', 'Content Writing'],
                    ['MGT825', 'Business Intelligence'],
                    ['MKT999', 'Digital Marketing'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['MKT050', 'Contemporary Marketing Issues'],
                    ['MKT138', 'International Marketing'],
                    ['MKT939', 'Marketing Strategy'],
                    ['MKT221', 'Research Project for Marketing', 6],
                ]),
            ],
            'BA-LPM' => [
                ...$bachelorCommon,
                ...$this->term(3, 1, [
                    ['MGT854', 'Logistics and Supply Chain Management I'],
                    ['MGT274', 'Purchasing and Supply Management'],
                    ['MGT843', 'Procurement and Purchasing Management'],
                    ['MGT826', 'Generative AI for Business'],
                    ['MGT134', 'Inventory and Warehouse Management I'],
                ]),
                ...$this->term(3, 2, [
                    ['MGT855', 'Logistics and Supply Chain Management II'],
                    ['MGT179', 'Operations Management'],
                    ['MGT844', 'Logistics and Procurement Analytics'],
                    ['MGT276', 'Distribution Management'],
                    ['MGT845', 'Inventory and Warehouse Management II'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT825', 'Business Intelligence'],
                    ['MGT952', 'Business Ethics'],
                    ['MGT846', 'Risk Management in Logistics and Procurement I'],
                    ['MGT848', 'Logistics Information Systems and Technology'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['MGT278', 'Transportation and Logistics Management'],
                    ['MGT279', 'Global Logistics and International Trade'],
                    ['MGT847', 'Risk Management in Logistics and Procurement II'],
                    ['MGT221', 'Research Project for Logistics and Procurement Management', 6],
                ]),
            ],
            'BBA-MKT' => [
                ...$associateCommon,
                ...$associateMarketing,
                ...$this->term(3, 1, [
                    ['MKT165', 'Marketing for Services'],
                    ['MKT226', 'Sales Management I'],
                    ['MGT826', 'Generative AI for Business'],
                    ['MKT119', 'Integrated Marketing Communications'],
                    ['MKT235', 'Marketing Workshop'],
                ]),
                ...$this->term(3, 2, [
                    ['MKT825', 'Social Media Management'],
                    ['MKT227', 'Sales Management II'],
                    ['MKT233', 'Promotion Management'],
                    ['MKT826', 'Digital Media Production'],
                    ['MKT936', 'Business to Business (B2B) Marketing'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT282', 'Value Chain Management'],
                    ['MKT827', 'Content Writing'],
                    ['MGT825', 'Business Intelligence'],
                    ['MKT908', 'Marketing Analytics'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['MKT050', 'Contemporary Marketing Issues'],
                    ['MKT138', 'International Marketing'],
                    ['MKT939', 'Marketing Strategy'],
                    ['MKT221', 'Research Project for Marketing', 6],
                ]),
            ],
            'BBA-HRMIR' => [
                ...$associateCommon,
                ...$associateHumanResources,
                ...$this->term(3, 1, [
                    ['MGT107', 'Human Resource Management II'],
                    ['MGT153', 'Human Resource Development'],
                    ['ECO152', 'Labor Economics'],
                    ['MGT826', 'Generative AI for Business'],
                    ['MGT131', 'Human Resource Planning'],
                ]),
                ...$this->term(3, 2, [
                    ['CST163', 'Human Resource Management Information System'],
                    ['ECO163', 'Industrial Economics and Development'],
                    ['MGT162', 'Employability and Skill Development'],
                    ['MGT189', 'Project Management'],
                    ['MGT237', 'Staff Selection and Appraisal'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT135', 'International Human Resource Management'],
                    ['MGT237', 'Strategic Management I'],
                    ['MGT825', 'Business Intelligence'],
                    ['LAW152', 'Labor Law'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['MKT175', 'Leadership and Management of Change'],
                    ['MGT238', 'Strategic Management II'],
                    ['MGT247', 'Total Quality Management'],
                    ['BUS231', 'Research Project for Human Resource Management and Industrial Relations', 6],
                ]),
            ],
            'BBA-EEM' => [
                ...$associateCommon,
                ...$associateEntrepreneurship,
                ...$this->term(3, 1, [
                    ['MGT106', 'Human Resource Management II'],
                    ['FIN095', 'Financial Management I'],
                    ['MKT168', 'Marketing Management II'],
                    ['TQ3', 'Introduction to Costing'],
                    ['MGT826', 'Generative AI for Business'],
                ]),
                ...$this->term(3, 2, [
                    ['MGT144', 'Business Planning and Development'],
                    ['MGT179', 'Operations Management'],
                    ['MGT962', 'Small & Medium Enterprise (SMEs)'],
                    ['MGT189', 'Project Management'],
                    ['MGT133', 'Franchise Management'],
                ]),
                ...$this->term(4, 1, [
                    ['MGT142', 'International Entrepreneurship'],
                    ['MGT237', 'Strategic Management I'],
                    ['MGT825', 'Business Intelligence'],
                    ['MGT952', 'Business Ethics'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['FAB306', 'Family Business Strategy'],
                    ['MGT238', 'Strategic Management II'],
                    ['MGT247', 'Total Quality Management'],
                    ['BUS231', 'Research Project for Entrepreneurship and Enterprise Management', 6],
                ]),
            ],
            'BBA-ACC' => [
                ...$associateCommon,
                ...$associateAccounting,
                ...$this->term(3, 1, [
                    ['FIN052', 'Auditing and Assurance Principles'],
                    ['TQ6A', 'Management Accounting I'],
                    ['MGT322', 'Financial Management'],
                    ['MGT826', 'Generative AI for Business'],
                    ['TQ5A', 'Financial Statement Preparation I'],
                ]),
                ...$this->term(3, 2, [
                    ['ACC302', 'Financial Reporting and Disclosure'],
                    ['TQ6B', 'Management Accounting II'],
                    ['TQ2', 'IT Skill and Software'],
                    ['TQ8', 'Cambodian Business and Company Law'],
                    ['TQ5B', 'Financial Statement Preparation II'],
                ]),
                ...$this->term(4, 1, [
                    ['FIN316', 'Advanced Financial Reporting and Disclosure I'],
                    ['ACC305', 'Advanced Auditing and Practices I'],
                    ['MGT825', 'Business Intelligence'],
                    ['ACC801', 'Law on Audit'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['FIN317', 'Advanced Financial Reporting and Disclosure II'],
                    ['ACC306', 'Advanced Auditing and Practices II'],
                    ['FIN320', 'Investment Management'],
                    ['BUS031', 'Research Project for Accounting', 6],
                ]),
            ],
            'BBA-FIN' => [
                ...$associateCommon,
                ...$associateFinance,
                ...$this->term(3, 1, [
                    ['FIN200', 'Public Finance'],
                    ['ACC301', 'Financial Reporting and Disclosure'],
                    ['FIN128', 'International Finance'],
                    ['MGT826', 'Generative AI for Business'],
                    ['ACC301-FIM', 'Financial Institutions Management I'],
                ]),
                ...$this->term(3, 2, [
                    ['FIN316', 'Credit and Lending Decisions I'],
                    ['FIN217', 'Financial Law and Regulations'],
                    ['FIN096', 'Financial Management I'],
                    ['MKT828', 'Stock Markets'],
                    ['ACC302', 'Financial Institutions Management II'],
                ]),
                ...$this->term(4, 1, [
                    ['FIN097', 'Financial Management II'],
                    ['ACC305', 'Risk Management and Insurance I'],
                    ['MGT825', 'Business Intelligence'],
                    ['FIN058', 'Credit and Lending Decisions II'],
                    ['MGT999', 'Research Writing'],
                ]),
                ...$this->term(4, 2, [
                    ['FIN902', 'Fin Tech'],
                    ['ACC306', 'Risk Management and Insurance II'],
                    ['FIN320', 'Investment Management'],
                    ['RPJ216', 'Research Project for Finance', 6],
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
