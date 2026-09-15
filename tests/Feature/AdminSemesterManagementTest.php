<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSemesterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_semesters_courses_teachers_and_students(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BCA'])->semesters()->where('number', 3)->first();

        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id, 'name' => 'Sam Student']);

        $response = $this->actingAs($admin)->get(route('admin.semesters.show', $semester));

        $response->assertOk();
        $response->assertSeeText('Data Structures');
        $response->assertSeeText('Taylor Teacher');
        $response->assertSeeText('Sam Student');
    }

    public function test_admin_can_add_a_course_from_the_semester_page(): void
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

        $response->assertRedirect(route('admin.semesters.show', $semester));

        $course = Course::where('code', 'CS201')->firstOrFail();
        $this->assertSame($semester->id, $course->semester_id);
        $this->assertSame($teacher->id, $course->teacher_id);
    }

    public function test_a_teacher_cannot_view_the_admin_semester_page(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $this->actingAs($teacher)->get(route('admin.semesters.show', $semester))->assertForbidden();
    }
}
