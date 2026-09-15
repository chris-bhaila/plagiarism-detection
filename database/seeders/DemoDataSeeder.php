<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Faculty;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a minimal, realistic set of demo data: two faculties (each with
     * 8 semesters), a teacher, an admin, a few students placed into
     * semesters, courses under those semesters, submissions, and fake
     * similarity reports. Also folds in the ad-hoc chris@student /
     * chris@teacher / chris@admin demo accounts so they survive
     * migrate:fresh --seed.
     *
     * Placeholder/demo courses live under BIM — BCA is reserved for the
     * real syllabus data (see BcaSyllabusSeeder) and shouldn't be
     * cluttered with fake ones.
     */
    public function run(): void
    {
        $bca = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $bim = Faculty::factory()->withSemesters()->create(['name' => 'BIM']);

        $bcaSemester1 = $bca->semesters()->where('number', 1)->first();
        $bimSemester1 = $bim->semesters()->where('number', 1)->first();
        $bimSemester2 = $bim->semesters()->where('number', 2)->first();

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

        $courseOne = $teacher->coursesTaught()->create([
            'semester_id' => $bimSemester1->id,
            'name' => 'Introduction to Computer Science',
            'code' => 'IM101',
        ]);

        $teacher->coursesTaught()->create([
            'semester_id' => $bimSemester2->id,
            'name' => 'Database Fundamentals',
            'code' => 'IM151',
        ]);

        $students = collect([
            ['name' => 'Sam Student', 'email' => 'sam@example.com', 'semester_id' => $bimSemester1->id],
            ['name' => 'Riley Student', 'email' => 'riley@example.com', 'semester_id' => $bimSemester1->id],
            ['name' => 'Jordan Student', 'email' => 'jordan@example.com', 'semester_id' => $bimSemester2->id],
        ])->map(fn (array $attrs) => User::factory()->create([
            ...$attrs,
            'role' => User::ROLE_STUDENT,
        ]));

        $submissionOne = Submission::factory()->create([
            'assignment_id' => Assignment::factory()->create([
                'course_id' => $courseOne->id,
                'title' => 'Essay: The History of the Internet',
                'description' => 'Write a 1000-word essay on the history and evolution of the internet.',
                'due_date' => now()->addWeek(),
                'similarity_threshold' => 0.35,
            ])->id,
            'student_id' => $students[0]->id,
            'text_content' => 'The internet began as a research project called ARPANET, funded by the United States Department of Defense in the late 1960s...',
            'submitted_at' => now()->subDays(2),
        ]);

        $submissionTwo = Submission::factory()->create([
            'assignment_id' => $submissionOne->assignment_id,
            'student_id' => $students[1]->id,
            'text_content' => 'ARPANET was a research project funded by the U.S. Department of Defense in the late 1960s, and it became the foundation of the modern internet...',
            'submitted_at' => now()->subDays(1),
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

        // Idempotent demo accounts, folded into the seeder so they survive
        // migrate:fresh --seed.
        User::updateOrCreate(
            ['email' => 'chris@student'],
            [
                'name' => 'Chris Student',
                'password' => Hash::make('student'),
                'role' => User::ROLE_STUDENT,
                'semester_id' => $bcaSemester1->id,
            ],
        );

        User::updateOrCreate(
            ['email' => 'chris@teacher'],
            [
                'name' => 'Chris Teacher',
                'password' => Hash::make('teacher'),
                'role' => User::ROLE_TEACHER,
                'semester_id' => null,
            ],
        );

        User::updateOrCreate(
            ['email' => 'chris@admin'],
            [
                'name' => 'Chris Admin',
                'password' => Hash::make('admin'),
                'role' => User::ROLE_ADMIN,
                'semester_id' => null,
            ],
        );
    }
}
