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

class TeacherStudentDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_a_students_status_in_their_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Essay One']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($teacher)->get(route('courses.students.show', [$course, $student]));

        $response->assertOk();
        $response->assertSeeText('Sam Student');
        $response->assertSeeText('Essay One');
        $response->assertSeeText($submission->submitted_at->format('j M'));
    }

    public function test_an_assignment_without_a_submission_shows_not_submitted(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Unattempted Essay']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($teacher)->get(route('courses.students.show', [$course, $student]));

        $response->assertOk();
        $response->assertSeeText('Unattempted Essay');
        $response->assertSeeText('Not submitted');
    }

    public function test_flagged_submission_shows_its_review_status(): void
    {
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

        $response = $this->actingAs($teacher)->get(route('courses.students.show', [$course, $student]));

        $response->assertOk();
        $response->assertSeeText('Confirmed');
    }

    public function test_a_teacher_cannot_view_a_student_in_another_teachers_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($teacher)->get(route('courses.students.show', [$course, $student]))->assertForbidden();
    }

    public function test_viewing_a_student_not_in_the_courses_semester_is_not_found(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $unrelatedStudent = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->actingAs($teacher)->get(route('courses.students.show', [$course, $unrelatedStudent]))->assertNotFound();
    }
}
