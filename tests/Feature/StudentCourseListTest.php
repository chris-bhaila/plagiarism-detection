<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseListTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_courses_in_their_own_semester(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Ada Lovelace']);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        Assignment::factory()->count(2)->create(['course_id' => $course->id]);

        $otherCourse = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $otherSemester->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($student)->get(route('student.courses.index'));

        $response->assertOk();
        $response->assertSee($course->name);
        $response->assertSee('Ada Lovelace');
        $response->assertSee('2 assignments');
        $response->assertDontSee($otherCourse->name);
    }

    public function test_unassigned_course_shows_unassigned_badge_instead_of_a_teacher(): void
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['teacher_id' => null, 'semester_id' => $semester->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($student)->get(route('student.courses.index'));

        $response->assertOk();
        $response->assertSee('Unassigned');
    }

    public function test_course_detail_shows_the_students_own_submission_status_per_assignment(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $submittedAssignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Done Already']);
        $openAssignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Not Started']);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        Submission::factory()->create(['assignment_id' => $submittedAssignment->id, 'student_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('student.courses.show', $course));

        $response->assertOk();
        $response->assertSee($course->name);
        $response->assertSeeInOrder(['Done Already', 'Submitted'], false);
        $response->assertSeeInOrder(['Not Started', 'Not submitted'], false);
    }

    public function test_a_student_cannot_view_a_course_outside_their_semester(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->actingAs($student)->get(route('student.courses.show', $course))->assertNotFound();
    }
}
