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

class SubmissionNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function submissionFor(User $teacher): Submission
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        return Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
    }

    public function test_teacher_can_add_a_note_to_a_submission_in_their_own_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $submission = $this->submissionFor($teacher);

        $response = $this->actingAs($teacher)->post(route('submissions.notes.store', $submission), [
            'body' => 'Please revise your citations before the deadline.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('submission_notes', [
            'submission_id' => $submission->id,
            'author_id' => $teacher->id,
            'body' => 'Please revise your citations before the deadline.',
        ]);
    }

    public function test_notes_accumulate_as_a_running_log(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $submission = $this->submissionFor($teacher);

        $this->actingAs($teacher)->post(route('submissions.notes.store', $submission), ['body' => 'First note.']);
        $this->actingAs($teacher)->post(route('submissions.notes.store', $submission), ['body' => 'Second note.']);

        $this->assertSame(2, $submission->notes()->count());
        // Newest first.
        $this->assertSame('Second note.', $submission->notes()->first()->body);
    }

    public function test_a_teacher_cannot_note_another_teachers_submission(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $submission = $this->submissionFor($otherTeacher);

        $this->actingAs($teacher)->post(route('submissions.notes.store', $submission), [
            'body' => 'Should not be allowed.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('submission_notes', ['submission_id' => $submission->id]);
    }

    public function test_note_body_is_required(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $submission = $this->submissionFor($teacher);

        $this->actingAs($teacher)->post(route('submissions.notes.store', $submission), ['body' => ''])
            ->assertSessionHasErrors(['body']);
    }

    public function test_admin_can_add_a_note_to_any_submission(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $submission = $this->submissionFor($teacher);

        $response = $this->actingAs($admin)->post(route('admin.submissions.notes.store', $submission), [
            'body' => 'Admin follow-up.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('submission_notes', [
            'submission_id' => $submission->id,
            'author_id' => $admin->id,
            'body' => 'Admin follow-up.',
        ]);
    }

    public function test_notes_appear_on_the_submissions_review_page(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $submission = $this->submissionFor($teacher);
        $submission->notes()->create(['author_id' => $teacher->id, 'body' => 'Great improvement here.']);

        $response = $this->actingAs($teacher)->get(route('assignments.submissions', $submission->assignment));

        $response->assertOk();
        $response->assertSeeText('Great improvement here.');
        $response->assertSeeText('Taylor Teacher');
    }

    public function test_teacher_can_add_a_note_from_the_similarity_report_page(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $submissionA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
        $submissionB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);
        $report = SimilarityReport::factory()->create([
            'submission_a_id' => $submissionA->id,
            'submission_b_id' => $submissionB->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('submissions.notes.store', $submissionA), [
            'body' => 'Please meet with me to discuss this.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('submission_notes', [
            'submission_id' => $submissionA->id,
            'body' => 'Please meet with me to discuss this.',
        ]);
    }

    public function test_the_similarity_report_page_shows_notes_for_both_submissions(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $submissionA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
        $submissionB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);
        $report = SimilarityReport::factory()->create([
            'submission_a_id' => $submissionA->id,
            'submission_b_id' => $submissionB->id,
        ]);
        $submissionA->notes()->create(['author_id' => $teacher->id, 'body' => 'Note for side A.']);
        $submissionB->notes()->create(['author_id' => $teacher->id, 'body' => 'Note for side B.']);

        $response = $this->actingAs($teacher)->get(route('similarity-reports.show', $report));

        $response->assertOk();
        $response->assertSeeText('Note for side A.');
        $response->assertSeeText('Note for side B.');
    }
}
