<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a minimal, realistic set of demo data: one teacher, one admin,
     * three students, one course, one assignment, a couple of submissions,
     * and fake similarity reports between them.
     */
    public function run(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Taylor Teacher',
            'email' => 'teacher@example.com',
            'role' => User::ROLE_TEACHER,
        ]);

        $admin = User::factory()->create([
            'name' => 'Ada Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $students = collect([
            ['name' => 'Sam Student', 'email' => 'sam@example.com'],
            ['name' => 'Riley Student', 'email' => 'riley@example.com'],
            ['name' => 'Jordan Student', 'email' => 'jordan@example.com'],
        ])->map(fn (array $attrs) => User::factory()->create([
            ...$attrs,
            'role' => User::ROLE_STUDENT,
        ]));

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Introduction to Computer Science',
            'code' => 'CS101',
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
            'description' => 'Write a 1000-word essay on the history and evolution of the internet.',
            'due_date' => now()->addWeek(),
            'similarity_threshold' => 0.4,
        ]);

        $submissionOne = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $students[0]->id,
            'text_content' => 'The internet began as a research project called ARPANET, funded by the United States Department of Defense in the late 1960s...',
            'submitted_at' => now()->subDays(2),
        ]);

        $submissionTwo = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $students[1]->id,
            'text_content' => 'ARPANET was a research project funded by the U.S. Department of Defense in the late 1960s, and it became the foundation of the modern internet...',
            'submitted_at' => now()->subDays(1),
        ]);

        $submissionThree = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $students[2]->id,
            'text_content' => 'Long before smartphones and social media, a handful of researchers connected a small number of computers to share information across long distances...',
            'submitted_at' => now(),
        ]);

        // Fake similarity report: submissions 1 and 2 are quite similar (flagged).
        SimilarityReport::factory()->create([
            'submission_a_id' => $submissionOne->id,
            'submission_b_id' => $submissionTwo->id,
            'lexical_score' => 0.62,
            'semantic_score' => 0.71,
            'combined_score' => 0.67,
            'status' => SimilarityReport::STATUS_PENDING,
            'matched_shingles' => [
                ['a_start' => 0, 'a_end' => 62, 'b_start' => 0, 'b_end' => 58, 'text' => 'ARPANET was a research project funded by the U.S. Department of Defense'],
            ],
        ]);

        // Fake similarity report: submissions 1 and 3 are not very similar.
        SimilarityReport::factory()->create([
            'submission_a_id' => $submissionOne->id,
            'submission_b_id' => $submissionThree->id,
            'lexical_score' => 0.12,
            'semantic_score' => 0.18,
            'combined_score' => 0.15,
            'status' => SimilarityReport::STATUS_DISMISSED,
            'matched_shingles' => null,
        ]);
    }
}
