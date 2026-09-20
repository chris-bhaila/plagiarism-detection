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

class TeacherWorkflowExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function scene(): array
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $alice = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Alice Aardvark', 'email' => 'alice@x.test']);
        $bob = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Bob Badger', 'email' => 'bob@x.test']);
        $subA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $alice->id, 'text_content' => 'Alice wrote this unique text.', 'submitted_at' => now()->subDay(), 'similarity_released_at' => null]);
        $subB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $bob->id, 'text_content' => 'Bob wrote something else.', 'submitted_at' => now(), 'similarity_released_at' => null]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        return compact('teacher', 'admin', 'course', 'assignment', 'subA', 'subB', 'otherTeacher');
    }

    public function test_teacher_can_bulk_release_and_hide_similarity_status(): void
    {
        $s = $this->scene();

        $this->actingAs($s['teacher'])
            ->patch(route('assignments.similarity-release.bulk', $s['assignment']), ['action' => 'release'])
            ->assertRedirect();

        $this->assertTrue($s['subA']->fresh()->isSimilarityReleased());
        $this->assertTrue($s['subB']->fresh()->isSimilarityReleased());

        $this->actingAs($s['teacher'])
            ->patch(route('assignments.similarity-release.bulk', $s['assignment']), ['action' => 'hide']);

        $this->assertFalse($s['subA']->fresh()->isSimilarityReleased());
        $this->assertFalse($s['subB']->fresh()->isSimilarityReleased());
    }

    public function test_bulk_release_keeps_the_original_release_time_and_rejects_bad_actions(): void
    {
        $s = $this->scene();
        $earlier = now()->subDays(3)->startOfSecond();
        $s['subA']->update(['similarity_released_at' => $earlier]);

        $this->actingAs($s['teacher'])->patch(route('assignments.similarity-release.bulk', $s['assignment']), ['action' => 'release']);
        $this->assertTrue($s['subA']->fresh()->similarity_released_at->equalTo($earlier));

        $this->actingAs($s['teacher'])
            ->patch(route('assignments.similarity-release.bulk', $s['assignment']), ['action' => 'nuke'])
            ->assertSessionHasErrors('action');
    }

    public function test_another_teacher_cannot_bulk_release_but_admin_can(): void
    {
        $s = $this->scene();

        $this->actingAs($s['otherTeacher'])
            ->patch(route('assignments.similarity-release.bulk', $s['assignment']), ['action' => 'release'])
            ->assertForbidden();
        $this->assertFalse($s['subA']->fresh()->isSimilarityReleased());

        $this->actingAs($s['admin'])
            ->patch(route('admin.assignments.similarity-release.bulk', $s['assignment']), ['action' => 'release'])
            ->assertRedirect();
        $this->assertTrue($s['subA']->fresh()->isSimilarityReleased());
    }

    public function test_owner_can_view_a_submissions_full_text_but_other_teachers_cannot(): void
    {
        $s = $this->scene();

        $this->actingAs($s['teacher'])->get(route('submissions.show', $s['subA']))
            ->assertOk()->assertSee('Alice wrote this unique text.')->assertSee('Alice Aardvark');

        $this->actingAs($s['otherTeacher'])->get(route('submissions.show', $s['subA']))->assertForbidden();

        $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['subA']))
            ->assertOk()->assertSee('Alice wrote this unique text.');
    }

    public function test_submission_text_is_escaped(): void
    {
        $s = $this->scene();
        $s['subA']->update(['text_content' => '<script>alert(1)</script> hello']);

        $this->actingAs($s['teacher'])->get(route('submissions.show', $s['subA']))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_teacher_can_edit_and_delete_their_own_note(): void
    {
        $s = $this->scene();
        $note = SubmissionNote::create(['submission_id' => $s['subA']->id, 'author_id' => $s['teacher']->id, 'body' => 'Typo hrere']);

        $this->actingAs($s['teacher'])->patch(route('submissions.notes.update', $note), ['body' => 'Typo here'])->assertRedirect();
        $this->assertSame('Typo here', $note->fresh()->body);

        $this->actingAs($s['teacher'])->patch(route('submissions.notes.update', $note), ['body' => ''])->assertSessionHasErrors('body');

        $this->actingAs($s['teacher'])->delete(route('submissions.notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('submission_notes', ['id' => $note->id]);
    }

    public function test_teacher_cannot_touch_someone_elses_note_or_another_courses_note(): void
    {
        $s = $this->scene();
        $adminNote = SubmissionNote::create(['submission_id' => $s['subA']->id, 'author_id' => $s['admin']->id, 'body' => 'From admin']);

        $this->actingAs($s['teacher'])->patch(route('submissions.notes.update', $adminNote), ['body' => 'Hijack'])->assertForbidden();
        $this->actingAs($s['teacher'])->delete(route('submissions.notes.destroy', $adminNote))->assertForbidden();

        $mine = SubmissionNote::create(['submission_id' => $s['subA']->id, 'author_id' => $s['teacher']->id, 'body' => 'Mine']);
        $this->actingAs($s['otherTeacher'])->patch(route('submissions.notes.update', $mine), ['body' => 'Hijack'])->assertForbidden();
        $this->actingAs($s['otherTeacher'])->delete(route('submissions.notes.destroy', $mine))->assertForbidden();

        $this->assertSame('From admin', $adminNote->fresh()->body);
        $this->assertSame('Mine', $mine->fresh()->body);
    }

    public function test_admin_can_edit_and_delete_any_note(): void
    {
        $s = $this->scene();
        $note = SubmissionNote::create(['submission_id' => $s['subA']->id, 'author_id' => $s['teacher']->id, 'body' => 'Original']);

        $this->actingAs($s['admin'])->patch(route('admin.submissions.notes.update', $note), ['body' => 'Moderated'])->assertRedirect();
        $this->assertSame('Moderated', $note->fresh()->body);
        $this->assertSame($s['teacher']->id, $note->fresh()->author_id);

        $this->actingAs($s['admin'])->delete(route('admin.submissions.notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('submission_notes', ['id' => $note->id]);
    }

    public function test_teacher_routes_for_notes_are_closed_to_students(): void
    {
        $s = $this->scene();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $s['course']->semester_id]);
        $note = SubmissionNote::create(['submission_id' => $s['subA']->id, 'author_id' => $s['teacher']->id, 'body' => 'x']);

        $this->actingAs($student)->delete(route('submissions.notes.destroy', $note))->assertForbidden();
        $this->actingAs($student)->get(route('submissions.show', $s['subA']))->assertForbidden();
    }

    public function test_submissions_page_search_and_sort(): void
    {
        $s = $this->scene();

        $this->actingAs($s['teacher'])->get(route('assignments.submissions', ['assignment' => $s['assignment'], 'search' => 'badger']))
            ->assertOk()->assertSee('Bob Badger')->assertDontSee('Alice Aardvark')
            ->assertSee('1 of 2 shown');

        $this->actingAs($s['teacher'])->get(route('assignments.submissions', ['assignment' => $s['assignment'], 'search' => 'alice@x.test']))
            ->assertSee('Alice Aardvark')->assertDontSee('Bob Badger');

        $this->actingAs($s['teacher'])->get(route('assignments.submissions', ['assignment' => $s['assignment'], 'sort' => 'name']))
            ->assertSeeInOrder(['Alice Aardvark', 'Bob Badger']);

        $this->actingAs($s['teacher'])->get(route('assignments.submissions', ['assignment' => $s['assignment'], 'sort' => 'submitted']))
            ->assertSeeInOrder(['Bob Badger', 'Alice Aardvark']);

        $this->actingAs($s['admin'])->get(route('admin.assignments.submissions', ['assignment' => $s['assignment'], 'search' => 'badger']))
            ->assertOk()->assertSee('Bob Badger')->assertDontSee('Alice Aardvark');
    }

    public function test_dashboard_can_be_filtered_to_one_course_within_scope(): void
    {
        $s = $this->scene();
        $second = Course::factory()->create(['teacher_id' => $s['teacher']->id, 'semester_id' => $s['course']->semester_id, 'name' => 'Second Course', 'code' => 'ZZZ900']);
        $foreign = Course::factory()->create(['teacher_id' => $s['otherTeacher']->id, 'semester_id' => $s['course']->semester_id, 'name' => 'Foreign Course']);

        $this->actingAs($s['teacher'])->get(route('dashboard', ['course' => $second->id]))
            ->assertOk()->assertViewHas('selectedCourse', fn ($c) => $c->id === $second->id)
            ->assertViewHas('totalSubmissions', 0);

        $this->actingAs($s['teacher'])->get(route('dashboard', ['course' => $s['course']->id]))
            ->assertViewHas('totalSubmissions', 2);

        // Someone else's course id is ignored: falls back to all of the teacher's own courses.
        $this->actingAs($s['teacher'])->get(route('dashboard', ['course' => $foreign->id]))
            ->assertOk()->assertViewHas('selectedCourse', null)->assertViewHas('totalSubmissions', 2)
            ->assertDontSee('Foreign Course');

        $this->actingAs($s['admin'])->get(route('dashboard', ['course' => $foreign->id]))
            ->assertViewHas('selectedCourse', fn ($c) => $c->id === $foreign->id);
    }
}
