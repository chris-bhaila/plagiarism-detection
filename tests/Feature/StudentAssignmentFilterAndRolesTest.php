<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssignmentFilterAndRolesTest extends TestCase
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
}
