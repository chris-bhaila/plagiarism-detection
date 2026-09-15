<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChrisDemoSeeder extends Seeder
{
    /**
     * Gives chris@teacher an actual course to look at: assigns them
     * CACS101 (BCA semester 1 — the same semester chris@student is
     * already in), adds 5 more students there (email
     * firstname.lastname@ics.dev, password firstname123, same pattern as
     * DemoTeachersSeeder but the student domain), and 2 assignments with
     * a mix of submitted/unsubmitted/flagged states so the new
     * courses.students.show page has something worth showing. Runs last
     * — after DemoTeachersSeeder's random assignment — so this course's
     * teacher deterministically ends up chris@teacher regardless of the
     * random shuffle.
     */
    protected array $students = [
        ['Anil', 'Bhandari'],
        ['Priya', 'Basnet'],
        ['Nabin', 'Koirala'],
        ['Sarita', 'Devkota'],
        ['Bishal', 'Khadka'],
    ];

    public function run(): void
    {
        $teacher = User::where('email', 'chris@teacher')->firstOrFail();
        $course = Course::where('code', 'CACS101')->firstOrFail();

        $course->update(['teacher_id' => $teacher->id]);

        $students = collect($this->students)->map(function (array $name) use ($course) {
            [$first, $last] = $name;
            $email = Str::lower("{$first}.{$last}@ics.dev");

            return User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$first} {$last}",
                    'password' => Hash::make(Str::lower($first).'123'),
                    'role' => User::ROLE_STUDENT,
                    'semester_id' => $course->semester_id,
                ],
            );
        });

        $assignmentOne = Assignment::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Assignment 1: History of Computing'],
            [
                'description' => 'Write a short essay on the history and evolution of computing.',
                'due_date' => now()->addWeek(),
                'similarity_threshold' => Assignment::DEFAULT_SIMILARITY_THRESHOLD,
            ],
        );

        $assignmentTwo = Assignment::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Quiz 1: Computer Basics'],
            [
                'description' => 'Short-answer quiz covering the fundamentals of computer hardware and software.',
                'due_date' => now()->addDays(3),
                'similarity_threshold' => Assignment::DEFAULT_SIMILARITY_THRESHOLD,
            ],
        );

        // Anil and Priya submit similar text on assignment 1 (flagged,
        // pending review); Nabin submits something unrelated (clean);
        // Sarita and Bishal leave it unsubmitted.
        $anilSubmission = Submission::updateOrCreate(
            ['assignment_id' => $assignmentOne->id, 'student_id' => $students[0]->id],
            [
                'text_content' => 'Computing has evolved from mechanical calculators in the 1800s to the mainframes of the 1950s and the personal computers of the 1980s...',
                'submitted_at' => now()->subDays(2),
            ],
        );

        $priyaSubmission = Submission::updateOrCreate(
            ['assignment_id' => $assignmentOne->id, 'student_id' => $students[1]->id],
            [
                'text_content' => 'Computing evolved from mechanical calculators in the 1800s to mainframes in the 1950s and personal computers in the 1980s...',
                'submitted_at' => now()->subDay(),
            ],
        );

        Submission::updateOrCreate(
            ['assignment_id' => $assignmentOne->id, 'student_id' => $students[2]->id],
            [
                'text_content' => 'The internet began as a research project connecting a handful of university computers, long before the World Wide Web existed...',
                'submitted_at' => now()->subHours(6),
            ],
        );

        SimilarityReport::updateOrCreate(
            ['submission_a_id' => $anilSubmission->id, 'submission_b_id' => $priyaSubmission->id],
            [
                'lexical_score' => 0.71,
                'semantic_score' => 0.68,
                'combined_score' => 0.70,
                'status' => SimilarityReport::STATUS_PENDING,
                'matched_shingles' => [
                    ['a_start' => 0, 'a_end' => 78, 'b_start' => 0, 'b_end' => 74, 'text' => 'Computing evolved from mechanical calculators in the 1800s to mainframes'],
                ],
            ],
        );
    }
}
