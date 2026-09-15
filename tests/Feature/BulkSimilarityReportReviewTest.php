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

class BulkSimilarityReportReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: SimilarityReport, 1: SimilarityReport}
     */
    protected function twoPendingReportsFor(User $teacher): array
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $reports = [];
        for ($i = 0; $i < 2; $i++) {
            $a = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
            $b = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
            $subA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $a->id]);
            $subB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $b->id]);
            $reports[] = SimilarityReport::factory()->create([
                'submission_a_id' => $subA->id,
                'submission_b_id' => $subB->id,
                'status' => SimilarityReport::STATUS_PENDING,
            ]);
        }

        return $reports;
    }

    public function test_teacher_can_bulk_dismiss_several_reports_at_once(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$reportOne, $reportTwo] = $this->twoPendingReportsFor($teacher);

        $response = $this->actingAs($teacher)->patch(route('similarity-reports.bulk-update-status'), [
            'report_ids' => [$reportOne->id, $reportTwo->id],
            'status' => SimilarityReport::STATUS_DISMISSED,
        ]);

        $response->assertRedirect();
        $this->assertSame(SimilarityReport::STATUS_DISMISSED, $reportOne->fresh()->status);
        $this->assertSame(SimilarityReport::STATUS_DISMISSED, $reportTwo->fresh()->status);
    }

    public function test_a_teacher_cannot_bulk_update_another_teachers_reports(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$ownReport] = $this->twoPendingReportsFor($teacher);
        [$foreignReport] = $this->twoPendingReportsFor($otherTeacher);

        $this->actingAs($teacher)->patch(route('similarity-reports.bulk-update-status'), [
            'report_ids' => [$ownReport->id, $foreignReport->id],
            'status' => SimilarityReport::STATUS_DISMISSED,
        ])->assertForbidden();

        // Neither report changed — the whole batch is rejected, not partially applied.
        $this->assertSame(SimilarityReport::STATUS_PENDING, $ownReport->fresh()->status);
        $this->assertSame(SimilarityReport::STATUS_PENDING, $foreignReport->fresh()->status);
    }

    public function test_admin_can_bulk_update_any_reports(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$reportOne, $reportTwo] = $this->twoPendingReportsFor($teacher);

        $response = $this->actingAs($admin)->patch(route('admin.similarity-reports.bulk-update-status'), [
            'report_ids' => [$reportOne->id, $reportTwo->id],
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ]);

        $response->assertRedirect();
        $this->assertSame(SimilarityReport::STATUS_CONFIRMED, $reportOne->fresh()->status);
        $this->assertSame(SimilarityReport::STATUS_CONFIRMED, $reportTwo->fresh()->status);
    }

    public function test_bulk_update_requires_at_least_one_report(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->patch(route('similarity-reports.bulk-update-status'), [
            'report_ids' => [],
            'status' => SimilarityReport::STATUS_DISMISSED,
        ])->assertSessionHasErrors(['report_ids']);
    }
}
