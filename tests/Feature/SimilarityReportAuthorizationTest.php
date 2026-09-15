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

class SimilarityReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function reportFor(User $teacher): SimilarityReport
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $subA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
        $subB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);

        return SimilarityReport::factory()->create([
            'submission_a_id' => $subA->id,
            'submission_b_id' => $subB->id,
            'status' => SimilarityReport::STATUS_PENDING,
        ]);
    }

    public function test_a_teacher_cannot_view_another_teachers_similarity_report(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $foreignReport = $this->reportFor(User::factory()->create(['role' => User::ROLE_TEACHER]));

        $this->actingAs($teacher)->get(route('similarity-reports.show', $foreignReport))
            ->assertForbidden();
    }

    public function test_a_teacher_cannot_update_another_teachers_report_status(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $foreignReport = $this->reportFor(User::factory()->create(['role' => User::ROLE_TEACHER]));

        $this->actingAs($teacher)->patch(route('similarity-reports.update-status', $foreignReport), [
            'status' => SimilarityReport::STATUS_DISMISSED,
        ])->assertForbidden();

        $this->assertSame(SimilarityReport::STATUS_PENDING, $foreignReport->fresh()->status);
    }

    public function test_owning_teacher_can_view_and_update_their_own_report(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $report = $this->reportFor($teacher);

        $this->actingAs($teacher)->get(route('similarity-reports.show', $report))
            ->assertOk();

        $this->actingAs($teacher)->patch(route('similarity-reports.update-status', $report), [
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ])->assertRedirect();

        $this->assertSame(SimilarityReport::STATUS_CONFIRMED, $report->fresh()->status);
    }
}
