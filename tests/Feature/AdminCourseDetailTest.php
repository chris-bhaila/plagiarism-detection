<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCourseDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_courses_assignments_and_roster(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Essay One']);

        $response = $this->actingAs($admin)->get(route('admin.courses.show', $course));

        $response->assertOk();
        $response->assertSeeText('Taylor Teacher');
        $response->assertSeeText('Essay One');
        $response->assertSeeText('Sam Student');
    }

    public function test_admin_can_drill_down_into_submissions_and_similarity_report(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Student A', 'semester_id' => $semester->id]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Student B', 'semester_id' => $semester->id]);

        $submissionA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
        $submissionB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);

        $report = SimilarityReport::factory()->create([
            'submission_a_id' => $submissionA->id,
            'submission_b_id' => $submissionB->id,
            'status' => SimilarityReport::STATUS_PENDING,
        ]);

        $submissionsResponse = $this->actingAs($admin)->get(route('admin.assignments.submissions', $assignment));
        $submissionsResponse->assertOk();
        $submissionsResponse->assertSeeText('Student A');
        $submissionsResponse->assertSeeText('Student B');

        $reportResponse = $this->actingAs($admin)->get(route('admin.similarity-reports.show', $report));
        $reportResponse->assertOk();
        $reportResponse->assertSeeText('Student A');
        $reportResponse->assertSeeText('Student B');

        // Admin has the same review capability as the course's teacher.
        $reportResponse->assertSee(route('admin.similarity-reports.update-status', $report), false);
    }

    public function test_admin_can_create_an_assignment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->post(route('admin.assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
        ]);

        $response->assertRedirect(route('admin.courses.show', $course));
        $this->assertDatabaseHas('assignments', ['course_id' => $course->id, 'title' => 'Essay: The History of the Internet']);
    }

    public function test_admin_can_update_a_similarity_reports_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
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
            'status' => SimilarityReport::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.similarity-reports.update-status', $report), [
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ]);

        $response->assertRedirect(route('admin.similarity-reports.show', $report));
        $this->assertSame(SimilarityReport::STATUS_CONFIRMED, $report->fresh()->status);
    }

    public function test_admin_can_attach_a_docx_file_when_creating_an_assignment(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $file = UploadedFile::fake()->create('brief.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($admin)->post(route('admin.assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
            'attachment' => $file,
        ]);

        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();
        $this->assertSame('brief.docx', $assignment->attachment_name);
        Storage::disk('local')->assertExists($assignment->attachment_path);
    }

    public function test_admin_can_always_download_an_assignment_attachment(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/brief.docx',
            'attachment_name' => 'brief.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/brief.docx', 'contents');

        $this->actingAs($admin)
            ->get(route('assignments.attachment', $assignment))
            ->assertOk();
    }

    public function test_an_enrolled_student_can_download_the_attachment_but_an_unenrolled_one_cannot(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/brief.docx',
            'attachment_name' => 'brief.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/brief.docx', 'contents');

        $enrolled = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $notEnrolled = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->actingAs($enrolled)->get(route('assignments.attachment', $assignment))->assertOk();
        $this->actingAs($notEnrolled)->get(route('assignments.attachment', $assignment))->assertForbidden();
    }

    public function test_a_teacher_cannot_view_admin_course_detail_pages(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->get(route('admin.courses.show', $course))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.assignments.submissions', $assignment))->assertForbidden();
    }

    public function test_admin_creating_an_assignment_with_a_blank_threshold_field_falls_back_to_the_default(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->post(route('admin.assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Q&A',
            'similarity_threshold' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();
        $this->assertEquals(Assignment::DEFAULT_SIMILARITY_THRESHOLD, $assignment->similarity_threshold);
    }

    public function test_admin_can_edit_an_assignment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Old Title']);

        $response = $this->actingAs($admin)->patch(route('admin.assignments.update', $assignment), [
            'title' => 'New Title',
        ]);

        $response->assertRedirect(route('admin.courses.show', $course));
        $this->assertSame('New Title', $assignment->fresh()->title);
    }

    public function test_admin_can_delete_an_assignment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->delete(route('admin.assignments.destroy', $assignment));

        $response->assertRedirect(route('admin.courses.show', $course));
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }
}
