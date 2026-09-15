<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class BcaSyllabusSeeder extends Seeder
{
    /**
     * The real BCA syllabus, semester by semester, per
     * https://itcollegenepal.com/bachelor-of-computer-application-syllabus/.
     *
     * Semesters VII and VIII each require 2 electives out of a longer
     * list — only the chosen ones are included here (see comments).
     * Courses are created unassigned (no teacher) — assign one per course
     * via the admin UI (Faculties → BCA → a semester → Edit on a course).
     *
     * Idempotent: safe to re-run (e.g. via `php artisan db:seed
     * --class=BcaSyllabusSeeder`) on any machine this repo is cloned to —
     * matches by [semester, code] and won't duplicate.
     */
    protected array $syllabus = [
        1 => [
            ['CACS101', 'Computer Fundamentals & Applications'],
            ['CACO102', 'Society and Technology'],
            ['CAEN103', 'English I'],
            ['CAMT104', 'Mathematics I'],
            ['CACS105', 'Digital Logic'],
        ],
        2 => [
            ['CACS151', 'C Programming'],
            ['CAAC152', 'Financial Accounting'],
            ['CAEN153', 'English II'],
            ['CAMT154', 'Mathematics II'],
            ['CACS155', 'Microprocessor and Computer Architecture'],
        ],
        3 => [
            ['CACS201', 'Data Structures and Algorithms'],
            ['CAST202', 'Probability and Statistics'],
            ['CACS203', 'System Analysis and Design'],
            ['CACS204', 'OOP in Java'],
            ['CACS205', 'Web Technology'],
        ],
        4 => [
            ['CACS251', 'Operating System'],
            ['CACS252', 'Numerical Methods'],
            ['CACS253', 'Software Engineering'],
            ['CACS254', 'Scripting Language'],
            ['CACS255', 'Database Management System'],
            ['CAPJ256', 'Project I'],
        ],
        5 => [
            ['CACS301', 'MIS and E-Business'],
            ['CACS302', 'DotNet Technology'],
            ['CACS303', 'Computer Networking'],
            ['CAMG304', 'Introduction to Management'],
            ['CACS305', 'Computer Graphics and Animation'],
        ],
        6 => [
            ['CACS351', 'Mobile Programming'],
            ['CACS352', 'Distributed System'],
            ['CAEC353', 'Applied Economics'],
            ['CACS354', 'Advanced Java Programming'],
            ['CACS355', 'Network Programming'],
            ['CAPJ356', 'Project II'],
        ],
        7 => [
            ['CACS401', 'Cyber Law and Professional Ethics'],
            ['CACS402', 'Cloud Computing'],
            ['CAIN403', 'Internship'],
            ['CACS410', 'Artificial Intelligence (Elective I)'],
            ['CACS404', 'Image Processing (Elective II)'],
        ],
        8 => [
            ['CAOR451', 'Operations Research'],
            ['CAPJ452', 'Project III'],
            ['CACS455', 'Data Analysis and Visualization (Elective III)'],
            ['CACS460', 'Internet of Things (Elective IV)'],
        ],
    ];

    public function run(): void
    {
        $bca = Faculty::firstOrCreate(['name' => 'BCA']);

        if ($bca->semesters()->count() === 0) {
            for ($number = 1; $number <= 8; $number++) {
                $bca->semesters()->create(['number' => $number]);
            }
        }

        foreach ($this->syllabus as $number => $courses) {
            $semester = $bca->semesters()->where('number', $number)->firstOrFail();

            foreach ($courses as [$code, $name]) {
                Course::updateOrCreate(
                    ['semester_id' => $semester->id, 'code' => $code],
                    ['name' => $name],
                );
            }
        }
    }
}
