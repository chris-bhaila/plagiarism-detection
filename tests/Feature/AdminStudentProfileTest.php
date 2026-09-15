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

class AdminStudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_students_profile_with_faculty_semester_courses_and_assignments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $semester = $faculty->semesters()->where('number', 3)->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures']);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Essay One']);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $student));

        $response->assertOk();
        $response->assertSeeText('Sam Student');
        $response->assertSeeText('BCA');
        $response->assertSeeText('3'); // semester number
        $response->assertSeeText('Data Structures');
        $response->assertSeeText('Taylor Teacher');
        $response->assertSeeText('Essay One');
        $response->assertSeeText($submission->submitted_at->format('j M'));
    }

    public function test_a_student_with_no_semester_shows_empty_state(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => null]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $student));

        $response->assertOk();
        $response->assertSeeText('Not placed in a semester with any courses yet.');
    }

    public function test_an_assignment_without_a_submission_shows_not_submitted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Unattempted Essay']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $student));

        $response->assertOk();
        $response->assertSeeText('Unattempted Essay');
        $response->assertSeeText('Not submitted');
    }

    public function test_a_teacher_cannot_view_a_students_profile(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($teacher)->get(route('admin.users.show', $student))->assertForbidden();
    }

    public function test_flagged_submission_shows_its_review_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        $otherSubmission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $otherStudent->id]);

        SimilarityReport::factory()->create([
            'submission_a_id' => $submission->id,
            'submission_b_id' => $otherSubmission->id,
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $student));

        $response->assertOk();
        $response->assertSeeText('Confirmed');
    }
}
