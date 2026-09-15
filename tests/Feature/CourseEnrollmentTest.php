<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_search_their_own_courses_by_name_or_code(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures', 'code' => 'CS201']);
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Operating Systems', 'code' => 'CS301']);

        $response = $this->actingAs($teacher)->get(route('courses.index', ['search' => 'Data']));

        $response->assertOk();
        $response->assertSeeText('Data Structures');
        $response->assertDontSeeText('Operating Systems');
    }

    public function test_teacher_course_search_does_not_leak_other_teachers_courses(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['teacher_id' => $otherTeacher->id, 'semester_id' => $semester->id, 'name' => 'Data Mining', 'code' => 'CS401']);

        $response = $this->actingAs($teacher)->get(route('courses.index', ['search' => 'Data']));

        $response->assertOk();
        $response->assertDontSeeText('Data Mining');
    }

    public function test_admin_can_create_a_course(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'name' => 'Data Structures',
            'code' => 'CS201',
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ]);

        $course = Course::where('code', 'CS201')->firstOrFail();

        $response->assertRedirect(route('admin.semesters.show', $semester));
        $this->assertSame($teacher->id, $course->teacher_id);
        $this->assertSame($semester->id, $course->semester_id);
    }

    public function test_course_codes_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semesterOne = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $semesterTwo = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['semester_id' => $semesterOne->id, 'code' => 'CS201']);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'name' => 'Data Structures Redux',
            'code' => 'CS201',
            'semester_id' => $semesterTwo->id,
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertSame(1, Course::where('code', 'CS201')->count());
    }

    public function test_updating_a_course_to_another_courses_code_fails(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['semester_id' => $semester->id, 'code' => 'CS101']);
        $course = Course::factory()->create(['semester_id' => $semester->id, 'code' => 'CS201']);

        $response = $this->actingAs($admin)->patch(route('admin.courses.update', $course), [
            'name' => $course->name,
            'code' => 'CS101',
            'semester_id' => $semester->id,
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertSame('CS201', $course->fresh()->code);
    }

    public function test_updating_a_course_while_keeping_its_own_code_succeeds(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['semester_id' => $semester->id, 'code' => 'CS201', 'name' => 'Old Name']);

        $response = $this->actingAs($admin)->patch(route('admin.courses.update', $course), [
            'name' => 'New Name',
            'code' => 'CS201',
            'semester_id' => $semester->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('New Name', $course->fresh()->name);
    }

    public function test_admin_can_create_a_course_without_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'name' => 'Data Structures',
            'code' => 'CS201',
            'semester_id' => $semester->id,
        ]);

        $course = Course::where('code', 'CS201')->firstOrFail();

        $response->assertRedirect(route('admin.semesters.show', $semester));
        $this->assertNull($course->teacher_id);
    }

    public function test_admin_can_assign_a_teacher_to_an_unassigned_course_via_edit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => null, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->patch(route('admin.courses.update', $course), [
            'name' => $course->name,
            'code' => $course->code,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect(route('admin.semesters.show', $semester));
        $this->assertSame($teacher->id, $course->fresh()->teacher_id);
    }

    public function test_course_creation_requires_name_code_and_semester(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), []);

        $response->assertSessionHasErrors(['name', 'code', 'semester_id']);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_a_teacher_cannot_create_a_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $this->actingAs($teacher)->post(route('admin.courses.store'), [
            'name' => 'Data Structures',
            'code' => 'CS201',
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ])->assertForbidden();
    }

    public function test_students_sharing_a_courses_semester_are_automatically_enrolled(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $inSemester = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $otherSemester->id]);

        $this->assertSame(1, $course->students()->count());
        $this->assertTrue($course->students()->get()->contains($inSemester));
    }

    public function test_a_teacher_cannot_view_another_teachers_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id]);

        $this->actingAs($teacher)
            ->get(route('courses.show', $course))
            ->assertForbidden();
    }

    public function test_a_students_assignments_list_is_scoped_to_their_semesters_courses(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $enrolledCourse = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $otherCourse = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $otherSemester->id]);

        Assignment::factory()->create([
            'course_id' => $enrolledCourse->id,
            'title' => 'Visible Assignment',
        ]);
        Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'title' => 'Hidden Assignment',
        ]);

        $response = $this->actingAs($student)->get(route('assignments.index'));

        $response->assertOk();
        $response->assertSeeText('Visible Assignment');
        $response->assertDontSeeText('Hidden Assignment');
    }

    public function test_admin_can_delete_a_course(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->delete(route('admin.courses.destroy', $course));

        $response->assertRedirect(route('admin.semesters.show', $semester));
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_deleting_a_course_cascades_to_its_assignments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->delete(route('admin.courses.destroy', $course));

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_a_teacher_cannot_delete_a_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $this->actingAs($teacher)->delete(route('admin.courses.destroy', $course))->assertForbidden();
    }
}
