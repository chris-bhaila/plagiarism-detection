<?php

namespace App\Services;

/**
 * Thin HTTP client for the external similarity-checking service.
 *
 * The actual detection logic (lexical shingling + Jaccard similarity,
 * combined with Sentence-BERT semantic similarity) lives in a separate
 * Python FastAPI service. This class is only responsible for talking to
 * it over HTTP.
 */
class SimilarityCheckClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.similarity_check.url');
    }

    /**
     * Send a submission's text to the similarity-check service and get
     * back its similarity scores against other submissions for the same
     * assignment.
     *
     * @return array{lexical_score: float, semantic_score: float, combined_score: float}
     */
    public function checkSubmission(int $submissionId, string $text, int $assignmentId): array
    {
        // TODO: wire this up to the real FastAPI endpoint, e.g.:
        //
        // $response = Http::baseUrl($this->baseUrl)
        //     ->timeout(30)
        //     ->post('/check', [
        //         'submission_id' => $submissionId,
        //         'assignment_id' => $assignmentId,
        //         'text' => $text,
        //     ]);
        //
        // if ($response->failed()) {
        //     Log::error('Similarity check request failed', [
        //         'submission_id' => $submissionId,
        //         'status' => $response->status(),
        //     ]);
        //
        //     throw new \RuntimeException('Similarity check service request failed.');
        // }
        //
        // return $response->json();

        return [
            'lexical_score' => 0.0,
            'semantic_score' => 0.0,
            'combined_score' => 0.0,
        ];
    }
}
