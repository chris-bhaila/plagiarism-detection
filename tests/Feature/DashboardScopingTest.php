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

class DashboardScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_only_sees_their_own_courses_on_the_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $ownCourse = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $otherCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id]);

        $ownAssignment = Assignment::factory()->create(['course_id' => $ownCourse->id, 'title' => 'My Own Assignment']);
        $otherAssignment = Assignment::factory()->create(['course_id' => $otherCourse->id, 'title' => 'Someone Elses Assignment']);

        // Give both assignments a flagged pair so they show up in "most flagged".
        foreach ([$ownAssignment, $otherAssignment] as $assignment) {
            $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
            $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
            $submissionA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
            $submissionB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);
            SimilarityReport::factory()->create([
                'submission_a_id' => $submissionA->id,
                'submission_b_id' => $submissionB->id,
                'combined_score' => 0.9,
            ]);
        }

        $response = $this->actingAs($teacher)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('My Own Assignment');
        $response->assertDontSeeText('Someone Elses Assignment');
    }

    public function test_an_admin_sees_every_course_on_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Some Assignment']);

        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        $submissionA = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentA->id]);
        $submissionB = Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $studentB->id]);
        SimilarityReport::factory()->create([
            'submission_a_id' => $submissionA->id,
            'submission_b_id' => $submissionB->id,
            'combined_score' => 0.9,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Some Assignment');
    }
}
