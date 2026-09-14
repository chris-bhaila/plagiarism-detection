<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_a_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($teacher)->post(route('courses.store'), [
            'name' => 'Data Structures',
            'code' => 'CS201',
        ]);

        $course = Course::where('code', 'CS201')->firstOrFail();

        $response->assertRedirect(route('courses.show', $course));
        $this->assertSame($teacher->id, $course->teacher_id);
        $this->assertSame('Data Structures', $course->name);
    }

    public function test_course_creation_requires_name_and_code(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($teacher)->post(route('courses.store'), []);

        $response->assertSessionHasErrors(['name', 'code']);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_a_teacher_cannot_reuse_their_own_course_code(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        Course::factory()->create(['teacher_id' => $teacher->id, 'code' => 'CS101']);

        $response = $this->actingAs($teacher)->post(route('courses.store'), [
            'name' => 'Another Course',
            'code' => 'CS101',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseCount('courses', 1);
    }

    public function test_search_filters_combine_with_and_logic(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $match = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Priya Sharma',
            'faculty' => 'BCA',
            'semester' => 4,
        ]);

        // Same name pattern but wrong faculty.
        User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Priya Gurung',
            'faculty' => 'BIM',
            'semester' => 4,
        ]);

        // Same faculty and semester but wrong name.
        User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Anish Rai',
            'faculty' => 'BCA',
            'semester' => 4,
        ]);

        $response = $this->actingAs($teacher)->get(
            route('courses.show', $course).'?search=Priya&faculty=BCA&semester=4',
        );

        $response->assertOk();
        $response->assertSeeText('Priya Sharma');
        $response->assertDontSeeText('Priya Gurung');
        $response->assertDontSeeText('Anish Rai');
    }

    public function test_already_enrolled_students_are_excluded_from_search(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $enrolled = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Already Enrolled',
            'faculty' => 'BCA',
            'semester' => 4,
        ]);

        $course->students()->attach($enrolled->id);

        $response = $this->actingAs($teacher)->get(
            route('courses.show', $course).'?faculty=BCA&semester=4',
        );

        $response->assertOk();
        // The student legitimately shows up in the roster (they're
        // enrolled) — what matters is they're excluded from candidates.
        $response->assertDontSee('name="student_ids[]" value="'.$enrolled->id.'"', false);
        $response->assertSeeText('No matching students found');
    }

    public function test_teacher_can_enroll_multiple_selected_students(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $studentA = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentB = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($teacher)->post(route('courses.enroll', $course), [
            'student_ids' => [$studentA->id, $studentB->id],
        ]);

        $response->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('course_user', ['course_id' => $course->id, 'user_id' => $studentA->id]);
        $this->assertDatabaseHas('course_user', ['course_id' => $course->id, 'user_id' => $studentB->id]);
        $this->assertSame(2, $course->students()->count());
    }

    public function test_teacher_cannot_enroll_students_into_another_teachers_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($teacher)
            ->get(route('courses.show', $course))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('courses.enroll', $course), ['student_ids' => [$student->id]])
            ->assertForbidden();

        $this->assertDatabaseMissing('course_user', ['course_id' => $course->id, 'user_id' => $student->id]);
    }

    public function test_a_students_assignments_list_is_scoped_to_enrolled_courses(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 3]);

        $enrolledCourse = Course::factory()->create(['teacher_id' => $teacher->id]);
        $otherCourse = Course::factory()->create(['teacher_id' => $teacher->id]);

        $enrolledCourse->students()->attach($student->id);

        $visibleAssignment = \App\Models\Assignment::factory()->create([
            'course_id' => $enrolledCourse->id,
            'title' => 'Visible Assignment',
        ]);
        \App\Models\Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'title' => 'Hidden Assignment',
        ]);

        $response = $this->actingAs($student)->get(route('assignments.index'));

        $response->assertOk();
        $response->assertSeeText('Visible Assignment');
        $response->assertDontSeeText('Hidden Assignment');
    }
}
