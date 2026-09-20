<?php

namespace App\Jobs;

use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use App\Services\SimilarityCheckClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs a submission's text against every other submission on the same
 * assignment and stores a SimilarityReport per comparison. Queued (not run
 * inline in the request) since the check is an external HTTP call whose
 * latency shouldn't be on the critical path of a student's submit click —
 * see the TODO this replaced in StudentAssignmentController::submit().
 * Needs a queue worker running (`php artisan queue:work`) outside of tests;
 * QUEUE_CONNECTION=sync in phpunit.xml runs this inline during tests, so
 * the existing submit-flow tests don't need to know it's queued at all.
 */
class CheckSubmissionSimilarity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function handle(SimilarityCheckClient $client, SimilarityReportRepositoryInterface $reports): void
    {
        $results = $client->checkSubmission(
            $this->submission->id,
            $this->submission->text_content,
            $this->submission->assignment_id,
        );

        foreach ($results as $result) {
            $reports->create([
                'submission_a_id' => $this->submission->id,
                'submission_b_id' => $result['compared_submission_id'],
                'lexical_score' => $result['lexical_score'],
                'semantic_score' => $result['semantic_score'],
                'combined_score' => $result['combined_score'],
                'matched_shingles' => $result['matched_shingles'] ?? null,
                'status' => $result['combined_score'] >= $this->submission->assignment->similarity_threshold
                    ? SimilarityReport::STATUS_PENDING
                    : SimilarityReport::STATUS_CLEARED,
            ]);
        }
    }
}
