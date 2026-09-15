<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_export_their_own_assignments_submissions_as_csv(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Essay One']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);
        Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($teacher)->get(route('assignments.submissions.export', $assignment));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('essay-one-scores.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Sam Student', $response->streamedContent());
    }

    public function test_a_teacher_cannot_export_another_teachers_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->get(route('assignments.submissions.export', $assignment))->assertForbidden();
    }

    public function test_admin_can_export_any_assignments_submissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Essay One']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);
        Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($admin)->get(route('admin.assignments.submissions.export', $assignment));

        $response->assertOk();
        $this->assertStringContainsString('Sam Student', $response->streamedContent());
    }
}
