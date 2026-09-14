<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integration test against the REAL Python FastAPI similarity-check
 * service — this deliberately does not Http::fake(). It needs the service
 * actually running at SIMILARITY_CHECK_API_URL so we can confirm the
 * request/response shapes genuinely match and get real scores back, not
 * stubbed placeholders.
 */
class SimilarityCheckIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $url = config('services.similarity_check.url');

        try {
            $healthy = Http::timeout(5)->get("{$url}/health")->successful();
        } catch (ConnectionException) {
            $healthy = false;
        }

        if (! $healthy) {
            $this->fail(
                "The similarity-check FastAPI service isn't reachable at {$url}/health. ".
                'Start it before running this test — it requires a live response, not a stub.'
            );
        }
    }

    public function test_submitting_creates_similarity_reports_with_real_scores(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'similarity_threshold' => 0.35,
        ]);

        $newText = <<<'TEXT'
        Climate change is one of the most pressing challenges facing humanity today. Rising global temperatures, driven primarily by the burning of fossil fuels, have led to more frequent and severe weather events, including hurricanes, droughts, and wildfires. Sea levels are rising as polar ice caps melt, threatening coastal communities around the world. Scientists agree that immediate and sustained action is required to reduce greenhouse gas emissions and transition to renewable energy sources such as solar and wind power. Governments, businesses, and individuals all have a role to play in mitigating the effects of climate change and building a more sustainable future for generations to come.
        TEXT;

        // Near-verbatim: same content, a couple of words swapped.
        $verbatimText = <<<'TEXT'
        Climate change is one of the most pressing challenges facing humanity today. Rising global temperatures, driven mainly by the burning of fossil fuels, have led to more frequent and severe weather events, including hurricanes, droughts, and wildfires. Sea levels are rising as polar ice caps melt, threatening coastal communities around the world. Scientists agree that immediate and sustained action is required to reduce greenhouse gas emissions and transition to renewable energy sources such as solar and wind power. Governments, businesses, and individuals all have a role to play in mitigating the effects of climate change and building a more sustainable future for generations to come.
        TEXT;

        // Full paraphrase: same ideas, different wording and structure.
        $paraphraseText = <<<'TEXT'
        Global warming represents one of the greatest threats our species has ever faced. Because we keep burning coal, oil, and gas, the planet keeps getting hotter, and that's fueling nastier storms, longer dry spells, and bigger wildfires than we used to see. As glaciers and ice sheets melt, oceans are creeping higher, putting cities along the coast at real risk. Researchers are pretty much unanimous that we need to act now, cutting down on carbon pollution and shifting toward clean power like wind turbines and solar panels. Everyone, from world leaders to everyday people, needs to pitch in if we want to leave a livable planet to the next generation.
        TEXT;

        // Unrelated topic entirely.
        $unrelatedText = <<<'TEXT'
        The history of jazz music traces back to the late nineteenth and early twentieth centuries in New Orleans, where African American musicians blended blues, ragtime, and brass band traditions into something entirely new. Early jazz pioneers like Buddy Bolden and later Louis Armstrong helped popularize improvisation as a central feature of the genre, allowing performers to reinterpret melodies in the moment rather than simply reproducing sheet music. Over the following decades, jazz evolved through swing, bebop, cool jazz, and free jazz, each era reflecting the cultural and social currents of its time. Today, jazz remains a living tradition, studied in conservatories and performed in clubs worldwide, prized for its emphasis on spontaneity, rhythm, and individual expression within a collaborative ensemble.
        TEXT;

        $verbatimSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
            'text_content' => $verbatimText,
        ]);

        $paraphraseSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
            'text_content' => $paraphraseText,
        ]);

        $unrelatedSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
            'text_content' => $unrelatedText,
        ]);

        $actingStudent = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 3]);

        // Exercise the real flow: HTTP -> route -> role middleware ->
        // StudentAssignmentController::submit() -> SimilarityCheckClient.
        $response = $this->actingAs($actingStudent)->post(
            route('assignments.submit', $assignment),
            ['text_content' => $newText],
        );

        $response->assertRedirect(route('assignments.submit.show', $assignment));

        $newSubmission = Submission::where('student_id', $actingStudent->id)
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        $reports = SimilarityReport::where('submission_a_id', $newSubmission->id)->get();

        $this->assertCount(3, $reports, 'Expected one SimilarityReport per existing submission.');

        $byCompared = $reports->keyBy('submission_b_id');

        $verbatimReport = $byCompared[$verbatimSubmission->id];
        $paraphraseReport = $byCompared[$paraphraseSubmission->id];
        $unrelatedReport = $byCompared[$unrelatedSubmission->id];

        foreach (['verbatim' => $verbatimReport, 'paraphrase' => $paraphraseReport, 'unrelated' => $unrelatedReport] as $label => $report) {
            $this->assertNotNull($report->lexical_score, "{$label}: lexical_score is null");
            $this->assertNotNull($report->semantic_score, "{$label}: semantic_score is null");
            $this->assertNotNull($report->combined_score, "{$label}: combined_score is null");
            $this->assertGreaterThanOrEqual(0.0, $report->lexical_score, "{$label}: lexical_score out of range");
            $this->assertLessThanOrEqual(1.0, $report->lexical_score, "{$label}: lexical_score out of range");
            $this->assertGreaterThanOrEqual(0.0, $report->semantic_score, "{$label}: semantic_score out of range");
            $this->assertLessThanOrEqual(1.0, $report->semantic_score, "{$label}: semantic_score out of range");
        }

        // None of these should look like the old stub's flat 0.0 placeholder.
        $this->assertFalse(
            (float) $verbatimReport->lexical_score === 0.0
                && (float) $verbatimReport->semantic_score === 0.0
                && (float) $verbatimReport->combined_score === 0.0,
            'Scores look like the old stub placeholder (all zero), not real API output.',
        );

        // Near-verbatim text should register as far more lexically similar
        // than either the paraphrase or the unrelated submission.
        $this->assertGreaterThan(
            $paraphraseReport->lexical_score,
            $verbatimReport->lexical_score,
            'Near-verbatim text should score more lexically similar than a paraphrase.',
        );
        $this->assertGreaterThan(
            $unrelatedReport->lexical_score,
            $verbatimReport->lexical_score,
            'Near-verbatim text should score more lexically similar than an unrelated submission.',
        );

        // The unrelated submission should end up least similar overall.
        $this->assertGreaterThan(
            $unrelatedReport->combined_score,
            $verbatimReport->combined_score,
            'Unrelated text should not out-score the near-verbatim copy on combined similarity.',
        );
        $this->assertGreaterThan(
            $unrelatedReport->combined_score,
            $paraphraseReport->combined_score,
            'Unrelated text should not out-score the paraphrase on combined similarity.',
        );

        $url = config('services.similarity_check.url');
        fwrite(STDERR, "\n\n--- Real similarity scores from {$url} ---\n");
        foreach (['verbatim' => $verbatimReport, 'paraphrase' => $paraphraseReport, 'unrelated' => $unrelatedReport] as $label => $report) {
            fwrite(STDERR, sprintf(
                "%-11s lexical=%.4f semantic=%.4f combined=%.4f status=%s shingles=%d\n",
                $label,
                $report->lexical_score,
                $report->semantic_score,
                $report->combined_score,
                $report->status,
                is_array($report->matched_shingles) ? count($report->matched_shingles) : 0,
            ));
        }
        fwrite(STDERR, "-----------------------------------------------------------\n\n");
    }

    public function test_submission_still_saves_when_the_similarity_service_is_unreachable(): void
    {
        config(['services.similarity_check.url' => 'http://127.0.0.1:9999']);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 3]);

        $response = $this->actingAs($student)->post(
            route('assignments.submit', $assignment),
            ['text_content' => 'A submission made while the similarity service is down.'],
        );

        $response->assertRedirect(route('assignments.submit.show', $assignment));
        $response->assertSessionHas('status');

        $submission = Submission::where('student_id', $student->id)->firstOrFail();

        $this->assertDatabaseCount('similarity_reports', 0);
        $this->assertSame('A submission made while the similarity service is down.', $submission->text_content);
    }
}
