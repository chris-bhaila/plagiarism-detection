<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimilarityCheckClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.similarity_check.url');
    }

    /**
     * Check a new submission against all existing submissions for the
     * same assignment. Returns an array of score results, one per
     * comparison, or an empty array if the check fails.
     */
    public function checkSubmission(int $submissionId, string $text, int $assignmentId, int $studentId): array
    {
        // Excludes every submission by this same student, not just this
        // exact row — a student may submit more than once (revision), and
        // without this a resubmission gets checked against the student's
        // own earlier attempt and can come back flagged as if it were
        // another student's work.
        $existingSubmissions = Submission::where('assignment_id', $assignmentId)
            ->where('student_id', '!=', $studentId)
            ->get(['id', 'text_content']);

        $checkWeb = (bool) config('services.similarity_check.check_web', true);

        // A lone submission with no other students to compare against can
        // still match a web source, so only skip the call entirely when
        // there's neither a peer submission nor a web check to run.
        if ($existingSubmissions->isEmpty() && ! $checkWeb) {
            return [];
        }

        $payload = [
            'new_submission' => [
                'id' => $submissionId,
                'text' => $text,
            ],
            'existing_submissions' => $existingSubmissions->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'text' => $submission->text_content,
                ];
            })->values()->toArray(),
            'check_web' => $checkWeb,
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/check-submission", $payload);

            if ($response->failed()) {
                Log::error('Similarity check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            return $response->json('results', []);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Could not reach similarity service', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }
}