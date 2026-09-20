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

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_stat_counts(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $submitted = Assignment::factory()->create(['course_id' => $course->id, 'due_date' => now()->addWeek()]);
        Assignment::factory()->create(['course_id' => $course->id, 'due_date' => now()->subWeek()]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        Submission::factory()->create(['assignment_id' => $submitted->id, 'student_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee($student->name);
        // 1 course, 1 submitted (of 2), 1 overdue.
        $response->assertSeeInOrder(['Courses', '1'], false);
        $response->assertSeeInOrder(['Submitted', '1'], false);
        $response->assertSeeInOrder(['Overdue', '1'], false);
    }

    public function test_due_soon_panel_lists_unsubmitted_assignments_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $submitted = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Already Done']);
        $notSubmitted = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Still Open']);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        Submission::factory()->create(['assignment_id' => $submitted->id, 'student_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('Still Open');
        $response->assertDontSee('Already Done');
    }

    public function test_recent_feedback_panel_lists_submissions_with_unseen_notes(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Feedback Assignment']);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        SubmissionNote::create([
            'submission_id' => $submission->id,
            'author_id' => $teacher->id,
            'body' => 'Please revise your citations.',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('Feedback Assignment');
        $response->assertSee('New feedback from your instructor');
    }
}
