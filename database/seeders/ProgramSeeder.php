<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Seed the programs shown for each university faculty.
     */
    public function run(): void
    {
        /** @var array<string, array<string, string>> $programsByFaculty */
        $programsByFaculty = [
            'FEAS' => [
                'BEAS' => 'Faculty of Economics and Agricultural Sciences (Bachelor)',
            ],
            'FBA' => [
                'ABA-ACC' => 'Associate of Business Administration in Accounting',
                'ABA-FIN' => 'Associate of Business Administration in Finance',
                'ABA-MKT' => 'Associate of Business Administration in Marketing',
                'ABA-EEM' => 'Associate of Business Administration in Entrepreneurship and Enterprise Management (Professional)',
                'ABA-HRMIR' => 'Associate of Business Administration in Human Resource Management and Industrial Relations',
                'BA-GM' => 'Bachelor of Arts in General Management',
                'BA-AF' => 'Bachelor of Arts in Accounting and Finance',
                'BA-FB' => 'Bachelor of Art in Finance and Banking',
                'BA-MKT' => 'Bachelor of Arts in Marketing',
                'BA-LPM' => 'Bachelor of Arts in Logistics and Procurement Management',
                'BBA-MKT' => 'Bachelor of Business Administration in Marketing',
                'BBA-HRMIR' => 'Bachelor of Business Administration in Human Resource Management and Industrial Relations (Professional)',
                'BBA-EEM' => 'Bachelor of Business Administration in Entrepreneurship and Enterprise Management (Professional)',
                'BBA-ACC' => 'Bachelor of Business Administration in Accounting (Professional)',
                'BBA-FIN' => 'Bachelor of Business Administration in Finance (Professional)',
                'FBA-MASTER' => "Faculty of Business Administration (Master's Degree)",
                'MFBM' => 'Master in Family Business Management (FAB)',
            ],
            'FTH' => [
                'ATM' => 'Associate of Tourism Management',
                'BATHM' => 'Bachelor of Arts in Tourism and Hospitality Management',
            ],
            'FST' => [
                'AIT' => 'Associate of Information Technology',
                'ADSAIE' => 'Associate of Data Science and AI Engineering',
                'ADMD' => 'Associate of Digital Media Design',
                'BIT' => 'Bachelor of Science in Information Technology',
                'BDSAIE' => 'Bachelor of Science in Data Science and AI Engineering',
                'BDMD' => 'Bachelor of Science in Digital Media Design',
                'MIT' => 'Master of Science in Information Technology',
                'ITC' => 'Centre for Information Technology',
            ],
            'FEA' => [
                'AAUP' => 'Associate of Architecture in Architecture and Urban Planning',
                'A-BCE' => 'Associate of Engineering in Building Civil Engineering',
                'A-BHCE' => 'Associate of Engineering in Bridge and Highway Civil Engineering',
                'A-EEE' => 'Associate of Engineering in Electrical and Electronics Engineering',
                'BA-AUP' => 'Bachelor of Arts in Architecture and Urban Planning',
                'BS-BCE' => 'Bachelor of Science in Building Civil Engineering',
                'BS-BHCE' => 'Bachelor of Science in Bridge and Highway Civil Engineering',
                'BS-EEE' => 'Bachelor of Science in Electrical and Electronic Engineering',
            ],
            'FAHL' => [
                'A-ENG' => 'Associate of English',
                'B-ENG-PRO' => 'Bachelor of English (Professional)',
                'BA-ENG' => 'Bachelor of Arts in English',
                'BA-TEFL' => 'Bachelor of Arts in Teaching English as a Foreign Language',
                'MED-EPM' => 'Master of Education (M.Ed.) in Educational Planning and Management Program',
                'MED-TEFL' => 'Master of Education (M.Ed.) in Teaching English as a Foreign Language Program',
                'ST-EL' => 'Short Training Courses on English Languages',
            ],
            'FLSS' => [
                'A-LAW' => 'Associate of Laws',
                'APA-PMG' => 'Associate of Public Administration in Public Management and Governance',
                'BPA-PMG' => 'Bachelor of Public Administration in Public Management and Governance',
                'BA-LAW' => 'Bachelor of Arts in Law',
                'B-LAW' => 'Bachelor of Laws',
                'LLM' => 'Master of Laws (LL.M)',
            ],
            'SDS' => [
                'PHD-MGT' => 'Doctor of Philosophy (Ph.D.) in Management',
                'PHD-ECON' => 'Doctor of Philosophy (Ph.D.) in Economics',
            ],
        ];

        $faculties = Faculty::query()
            ->whereIn('code', array_keys($programsByFaculty))
            ->get()
            ->keyBy('code');

        foreach ($programsByFaculty as $facultyCode => $programs) {
            $faculty = $faculties->get($facultyCode);

            if (! $faculty instanceof Faculty) {
                throw new \RuntimeException("Faculty {$facultyCode} must be seeded before its programs.");
            }

            foreach ($programs as $code => $name) {
                Program::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'faculty_id' => $faculty->id,
                        'name' => $name,
                    ],
                );
            }
        }
    }
}
