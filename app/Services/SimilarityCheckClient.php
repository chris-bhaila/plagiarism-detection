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
    public function checkSubmission(int $submissionId, string $text, int $assignmentId): array
    {
        $existingSubmissions = Submission::where('assignment_id', $assignmentId)
            ->where('id', '!=', $submissionId)
            ->get(['id', 'text_content']);

        if ($existingSubmissions->isEmpty()) {
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