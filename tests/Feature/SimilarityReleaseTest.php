<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimilarityReleaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Submission, 1: Course}
     */
    protected function flaggedSubmissionFor(User $teacher): array
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'similarity_threshold' => 0.35]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        $otherSubmission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $other->id]);

        SimilarityReport::factory()->create([
            'submission_a_id' => $submission->id,
            'submission_b_id' => $otherSubmission->id,
            'combined_score' => 0.9,
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ]);

        return [$submission, $course];
    }

    public function test_teacher_can_release_and_unrelease_their_own_submission(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$submission] = $this->flaggedSubmissionFor($teacher);

        $this->actingAs($teacher)->patch(route('submissions.similarity-release.update', $submission))
            ->assertRedirect();
        $this->assertNotNull($submission->fresh()->similarity_released_at);

        $this->actingAs($teacher)->patch(route('submissions.similarity-release.update', $submission))
            ->assertRedirect();
        $this->assertNull($submission->fresh()->similarity_released_at);
    }

    public function test_a_teacher_cannot_release_another_teachers_submission(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$submission] = $this->flaggedSubmissionFor($owner);

        $this->actingAs($teacher)->patch(route('submissions.similarity-release.update', $submission))
            ->assertForbidden();
        $this->assertNull($submission->fresh()->similarity_released_at);
    }

    public function test_admin_can_release_any_submission(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$submission] = $this->flaggedSubmissionFor($teacher);

        $this->actingAs($admin)->patch(route('admin.submissions.similarity-release.update', $submission))
            ->assertRedirect();
        $this->assertNotNull($submission->fresh()->similarity_released_at);
    }

    public function test_student_does_not_see_status_until_released(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$submission] = $this->flaggedSubmissionFor($teacher);

        $response = $this->actingAs($submission->student)->get(route('assignments.submit.show', $submission->assignment));

        $response->assertOk();
        $response->assertSee('In progress');
        $response->assertDontSee('Flagged — contact your instructor');
    }

    public function test_student_sees_status_once_released(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$submission] = $this->flaggedSubmissionFor($teacher);

        $this->actingAs($teacher)->patch(route('submissions.similarity-release.update', $submission));

        $response = $this->actingAs($submission->student)->get(route('assignments.submit.show', $submission->assignment));

        $response->assertOk();
        $response->assertSee('Flagged — contact your instructor');
    }
}
