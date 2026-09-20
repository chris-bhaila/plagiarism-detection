<?php

namespace Tests\Feature;

use App\Jobs\CheckSubmissionSimilarity;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Confirms the similarity check is dispatched as a queued job (not run
 * inline in the request) — see App\Jobs\CheckSubmissionSimilarity and the
 * TODO it replaced in StudentAssignmentController::submit(). The actual
 * scoring behavior of the job itself is covered end-to-end (with the queue
 * running synchronously via QUEUE_CONNECTION=sync in phpunit.xml) by
 * SimilarityCheckIntegrationTest.
 */
class SimilarityCheckIsQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_dispatches_a_queued_similarity_check_job(): void
    {
        Queue::fake();

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $course->semester_id]);

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'text_content' => 'A submission that should trigger a queued similarity check.',
        ])->assertRedirect(route('assignments.submit.show', $assignment));

        Queue::assertPushed(CheckSubmissionSimilarity::class, function (CheckSubmissionSimilarity $job) use ($student, $assignment) {
            return $job->submission->student_id === $student->id
                && $job->submission->assignment_id === $assignment->id;
        });
    }
}
