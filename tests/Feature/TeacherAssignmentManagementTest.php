<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_an_assignment_for_their_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
            'due_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'similarity_threshold' => 0.4,
        ]);

        $response->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
        ]);
    }

    public function test_creating_an_assignment_without_a_threshold_falls_back_to_the_default(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Q&A',
            // A blank number input submits as an empty string, not an
            // absent key — that's what actually triggered the bug.
            'similarity_threshold' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();
        $this->assertEquals(Assignment::DEFAULT_SIMILARITY_THRESHOLD, $assignment->similarity_threshold);
    }

    public function test_editing_an_assignment_without_a_threshold_keeps_the_existing_one(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'similarity_threshold' => 0.6]);

        $response = $this->actingAs($teacher)->patch(route('assignments.update', $assignment), [
            'title' => 'Renamed',
            'similarity_threshold' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(0.6, $assignment->fresh()->similarity_threshold);
    }

    public function test_assignment_creation_requires_a_title(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_teacher_can_attach_a_docx_file_when_creating_an_assignment(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $file = UploadedFile::fake()->create('brief.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('courses.show', $course));

        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();
        $this->assertSame('brief.docx', $assignment->attachment_name);
        Storage::disk('local')->assertExists($assignment->attachment_path);
    }

    public function test_assignment_attachment_rejects_non_docx_files(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $file = UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Essay: The History of the Internet',
            'attachment' => $file,
        ]);

        $response->assertSessionHasErrors(['attachment']);
        $this->assertDatabaseMissing('assignments', ['course_id' => $course->id]);
    }

    public function test_the_owning_teacher_can_download_the_attachment(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/brief.docx',
            'attachment_name' => 'brief.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/brief.docx', 'contents');

        $this->actingAs($teacher)
            ->get(route('assignments.attachment', $assignment))
            ->assertOk();
    }

    public function test_a_different_teacher_cannot_download_the_attachment(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/brief.docx',
            'attachment_name' => 'brief.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/brief.docx', 'contents');

        $this->actingAs($otherTeacher)
            ->get(route('assignments.attachment', $assignment))
            ->assertForbidden();
    }

    public function test_teacher_can_delete_their_own_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($teacher)->delete(route('assignments.destroy', $assignment));

        $response->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_a_teacher_cannot_delete_another_teachers_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->delete(route('assignments.destroy', $assignment))->assertForbidden();
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    }

    public function test_a_teacher_cannot_create_an_assignment_in_another_teachers_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $foreignCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);

        $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $foreignCourse->id,
            'title' => 'Sneaky assignment',
        ])->assertSessionHasErrors(['course_id']);

        $this->assertDatabaseMissing('assignments', ['course_id' => $foreignCourse->id]);
    }

    public function test_a_teacher_cannot_view_another_teachers_submissions(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->get(route('assignments.submissions', $assignment))->assertForbidden();
    }

    public function test_teacher_can_edit_their_own_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Old Title']);

        $response = $this->actingAs($teacher)->patch(route('assignments.update', $assignment), [
            'title' => 'New Title',
            'similarity_threshold' => 0.5,
        ]);

        $response->assertRedirect(route('courses.show', $course));
        $this->assertSame('New Title', $assignment->fresh()->title);
        $this->assertEquals(0.5, $assignment->fresh()->similarity_threshold);
    }

    public function test_a_teacher_cannot_edit_another_teachers_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->get(route('assignments.edit', $assignment))->assertForbidden();
        $this->actingAs($teacher)->patch(route('assignments.update', $assignment), ['title' => 'Hacked'])->assertForbidden();
    }

    public function test_uploading_a_new_attachment_replaces_the_old_one(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/old.docx',
            'attachment_name' => 'old.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/old.docx', 'old contents');

        $newFile = UploadedFile::fake()->create('new.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($teacher)->patch(route('assignments.update', $assignment), [
            'title' => $assignment->title,
            'attachment' => $newFile,
        ]);

        $assignment->refresh();
        $this->assertSame('new.docx', $assignment->attachment_name);
        Storage::disk('local')->assertMissing('assignment-attachments/old.docx');
        Storage::disk('local')->assertExists($assignment->attachment_path);
    }

    public function test_removing_an_attachment_without_replacing_it(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'attachment_path' => 'assignment-attachments/old.docx',
            'attachment_name' => 'old.docx',
        ]);
        Storage::disk('local')->put('assignment-attachments/old.docx', 'old contents');

        $this->actingAs($teacher)->patch(route('assignments.update', $assignment), [
            'title' => $assignment->title,
            'remove_attachment' => '1',
        ]);

        $assignment->refresh();
        $this->assertNull($assignment->attachment_path);
        $this->assertNull($assignment->attachment_name);
        Storage::disk('local')->assertMissing('assignment-attachments/old.docx');
    }
}
