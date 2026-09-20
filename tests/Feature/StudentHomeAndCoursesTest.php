<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\SubmissionNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHomeAndCoursesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpStudent(): array
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        return [$teacher, $semester, $student];
    }

    public function test_assignment_list_search_matches_title_and_course_code(): void
    {
        [$teacher, $semester, $student] = $this->setUpStudent();
        $networks = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'code' => 'NET101']);
        $maths = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'code' => 'MAT202']);
        Assignment::factory()->create(['course_id' => $networks->id, 'title' => 'Packet Essay']);
        Assignment::factory()->create(['course_id' => $maths->id, 'title' => 'Calculus Sheet']);

        $this->actingAs($student)->get(route('assignments.index', ['search' => 'packet']))
            ->assertOk()->assertSee('Packet Essay')->assertDontSee('Calculus Sheet');

        $this->actingAs($student)->get(route('assignments.index', ['search' => 'MAT202']))
            ->assertOk()->assertSee('Calculus Sheet')->assertDontSee('Packet Essay');
    }

    public function test_assignment_list_status_filter(): void
    {
        [$teacher, $semester, $student] = $this->setUpStudent();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $done = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Done One', 'due_date' => now()->addWeek()]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Open One', 'due_date' => now()->addWeek()]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Late One', 'due_date' => now()->subWeek()]);
        Submission::factory()->create(['assignment_id' => $done->id, 'student_id' => $student->id]);

        $this->actingAs($student)->get(route('assignments.index', ['status' => 'submitted']))
            ->assertSee('Done One')->assertDontSee('Open One')->assertDontSee('Late One');
        $this->actingAs($student)->get(route('assignments.index', ['status' => 'overdue']))
            ->assertSee('Late One')->assertDontSee('Open One')->assertDontSee('Done One');
        $this->actingAs($student)->get(route('assignments.index', ['status' => 'not_submitted']))
            ->assertSee('Open One')->assertDontSee('Late One')->assertDontSee('Done One');
        $this->actingAs($student)->get(route('assignments.index', ['status' => 'bogus']))
            ->assertOk()->assertSee('Done One')->assertSee('Open One')->assertSee('Late One');
    }

    public function test_course_list_shows_only_own_semester_with_progress_and_unassigned_teacher(): void
    {
        [$teacher, $semester, $student] = $this->setUpStudent();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $mine = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'My Networks']);
        Course::factory()->create(['teacher_id' => null, 'semester_id' => $semester->id, 'name' => 'Orphan Course']);
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $otherSemester->id, 'name' => 'Someone Elses']);
        $a1 = Assignment::factory()->create(['course_id' => $mine->id]);
        Assignment::factory()->create(['course_id' => $mine->id]);
        Submission::factory()->create(['assignment_id' => $a1->id, 'student_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('student.courses.index'));

        $response->assertOk()
            ->assertSee('My Networks')->assertSee($teacher->name)
            ->assertSee('1 / 2', false)
            ->assertSee('Orphan Course')->assertSee('Unassigned')
            ->assertDontSee('Someone Elses');
    }

    public function test_course_list_is_student_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->get(route('student.courses.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.dashboard'))->assertForbidden();
    }

    public function test_student_home_route_is_the_dashboard(): void
    {
        [, , $student] = $this->setUpStudent();

        $this->assertSame('student.dashboard', $student->homeRouteName());
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    }

    public function test_dashboard_shows_counts_overdue_and_upcoming(): void
    {
        [$teacher, $semester, $student] = $this->setUpStudent();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $done = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Finished Task', 'due_date' => now()->addWeek()]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Coming Soon', 'due_date' => now()->addDays(2)]);
        Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Missed Deadline', 'due_date' => now()->subDay()]);
        $submission = Submission::factory()->create(['assignment_id' => $done->id, 'student_id' => $student->id]);
        SubmissionNote::create(['submission_id' => $submission->id, 'author_id' => $teacher->id, 'body' => 'Cite better']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Coming Soon')
            ->assertSee('Missed Deadline')
            ->assertSee('Finished Task')
            ->assertViewHas('submittedCount', 1)
            ->assertViewHas('assignmentCount', 3)
            ->assertViewHas('noteCount', 1);
    }

    public function test_dashboard_hides_similarity_status_until_released(): void
    {
        [$teacher, $semester, $student] = $this->setUpStudent();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $mine = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'similarity_released_at' => null]);
        $theirs = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $other->id]);
        SimilarityReport::factory()->create([
            'submission_a_id' => $mine->id,
            'submission_b_id' => $theirs->id,
            'status' => SimilarityReport::STATUS_CONFIRMED,
        ]);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()->assertSee('In progress')->assertDontSee('Flagged');

        $mine->update(['similarity_released_at' => now()]);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()->assertSee('Flagged');
    }
}
