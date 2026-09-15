<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Submission;
use App\Models\SubmissionNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssignmentExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_open_an_assignment_outside_their_semester(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->actingAs($student)->get(route('assignments.submit.show', $assignment))->assertNotFound();
    }

    public function test_a_student_cannot_submit_to_an_assignment_outside_their_semester(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'text_content' => 'Sneaky submission from outside the course.',
        ])->assertNotFound();

        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_assignments_list_shows_submitted_and_not_submitted_badges(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $submittedAssignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Done Already', 'due_date' => now()->addWeek()]);
        $openAssignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Not Started', 'due_date' => now()->addWeek()]);
        $overdueAssignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Missed It', 'due_date' => now()->subWeek()]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        Submission::factory()->create(['assignment_id' => $submittedAssignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('assignments.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Done Already', 'Submitted'], false);
        $response->assertSeeInOrder(['Not Started', 'Not submitted'], false);
        $response->assertSeeInOrder(['Missed It', 'Overdue'], false);
    }

    public function test_receipt_page_shows_previous_attempts_after_a_revision(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now()->subDay(),
        ]);
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('assignments.submit.show', $assignment));

        $response->assertOk();
        $response->assertSee('Previous attempts');
    }

    public function test_receipt_page_shows_read_only_feedback_from_teacher(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        SubmissionNote::create([
            'submission_id' => $submission->id,
            'author_id' => $teacher->id,
            'body' => 'Please double-check your citations.',
        ]);

        $response = $this->actingAs($student)->get(route('assignments.submit.show', $assignment));

        $response->assertOk();
        $response->assertSee('Feedback from your instructor');
        $response->assertSee('Please double-check your citations.');

        // Read-only: no form action posts to the notes-store route on this page.
        $response->assertDontSee(route('submissions.notes.store', $submission), false);
    }
}
