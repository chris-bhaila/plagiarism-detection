<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\SubmissionNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentExperienceDemoSeeder extends Seeder
{
    /**
     * Gives chris@student a submission history worth looking at on
     * ChrisDemoSeeder's "Assignment 1: History of Computing" — a rough
     * first draft plus a revised final attempt, a similarity report on the
     * final attempt that's already released, and a short back-and-forth
     * feedback thread from chris@teacher. Exists to demo three of the
     * student-side features that have no other seeded example: submission
     * history (multiple attempts), the release-gated similarity status,
     * and the read-only feedback thread. Runs last, after ChrisDemoSeeder,
     * since it depends on that seeder's course/assignment/roster.
     *
     * Idempotent: submissions are matched on [assignment_id, student_id,
     * text_content] (the two attempts' text differs, so that combination
     * is a stable natural key here — a plain [assignment_id, student_id]
     * key would collapse the two attempts into one on a rerun); the report
     * and notes are matched on their own natural keys as usual.
     */
    public function run(): void
    {
        $student = User::where('email', 'chris@student')->firstOrFail();
        $teacher = User::where('email', 'chris@teacher')->firstOrFail();
        $assignment = Assignment::where('title', 'Assignment 1: History of Computing')->firstOrFail();

        $draftText = 'Draft: computing evolved from punch cards to microprocessors, though I still need to add my sources.';
        $finalText = 'Computing evolved from punch cards and vacuum tubes to microprocessors, driven by advances described in Ceruzzi (2003) and Campbell-Kelly (2004).';

        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id, 'text_content' => $draftText],
            ['submitted_at' => now()->subDays(3)],
        );

        $finalSubmission = Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id, 'text_content' => $finalText],
            ['submitted_at' => now()->subDay(), 'similarity_released_at' => now()->subHours(2)],
        );

        // Compare against Anil's earlier submission on the same assignment
        // — topically related but not a close match, landing just above
        // the assignment's default threshold.
        $anilSubmission = Submission::where('assignment_id', $assignment->id)
            ->whereHas('student', fn ($q) => $q->where('email', 'anil.bhandari@ics.dev'))
            ->firstOrFail();

        SimilarityReport::updateOrCreate(
            ['submission_a_id' => $finalSubmission->id, 'submission_b_id' => $anilSubmission->id],
            [
                'lexical_score' => 0.46,
                'semantic_score' => 0.39,
                'combined_score' => 0.42,
                'status' => SimilarityReport::STATUS_REVIEWED,
                'matched_shingles' => [
                    ['a_start' => 0, 'a_end' => 25, 'b_start' => 0, 'b_end' => 25, 'text' => 'Computing evolved from'],
                ],
            ],
        );

        SubmissionNote::updateOrCreate(
            ['submission_id' => $finalSubmission->id, 'author_id' => $teacher->id, 'body' => 'Good structure — please add citations for your sources.'],
        );

        SubmissionNote::updateOrCreate(
            ['submission_id' => $finalSubmission->id, 'author_id' => $teacher->id, 'body' => 'Citations look good now. Nicely revised!'],
        );
    }
}
