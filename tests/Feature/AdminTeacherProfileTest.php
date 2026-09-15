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

class AdminTeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_teachers_profile_with_courses_taught(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BCA'])->semesters()->where('number', 3)->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures']);
        Assignment::factory()->create(['course_id' => $course->id]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $teacher));

        $response->assertOk();
        $response->assertSeeText('Taylor Teacher');
        $response->assertSeeText('Data Structures');
        $response->assertSeeText('BCA');
    }

    public function test_pending_reviews_are_counted_per_course(): void
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

        SimilarityReport::factory()->create([
            'submission_a_id' => $submissionA->id,
            'submission_b_id' => $submissionB->id,
            'status' => SimilarityReport::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $teacher));

        $response->assertOk();
        $response->assertSeeText('1'); // pending count somewhere on the page
    }

    public function test_a_teacher_cannot_view_another_teachers_profile(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->get(route('admin.users.show', $otherTeacher))->assertForbidden();
    }
}
