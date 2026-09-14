<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_teachers_list(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        Course::factory()->count(2)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($admin)->get(route('admin.teachers'));

        $response->assertOk();
        $response->assertSeeText('Taylor Teacher');
        $response->assertSeeText('2'); // courses taught count
    }

    public function test_teachers_list_excludes_students(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Some Student']);

        $response = $this->actingAs($admin)->get(route('admin.teachers'));

        $response->assertOk();
        $response->assertDontSeeText('Some Student');
    }

    public function test_admin_can_filter_students_by_faculty_and_semester(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Match Student', 'faculty' => 'BCA', 'semester' => 4]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Wrong Faculty', 'faculty' => 'BIM', 'semester' => 4]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Wrong Semester', 'faculty' => 'BCA', 'semester' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.students', ['faculty' => 'BCA', 'semester' => 4]));

        $response->assertOk();
        $response->assertSeeText('Match Student');
        $response->assertDontSeeText('Wrong Faculty');
        $response->assertDontSeeText('Wrong Semester');
    }

    public function test_students_list_shows_enrolled_course_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Enrolled Student']);
        $course->students()->attach($student->id);

        $response = $this->actingAs($admin)->get(route('admin.students'));

        $response->assertOk();
        $response->assertSeeText('Enrolled Student');
    }

    public function test_students_list_excludes_teachers(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Some Teacher']);

        $response = $this->actingAs($admin)->get(route('admin.students'));

        $response->assertOk();
        $response->assertDontSeeText('Some Teacher');
    }

    public function test_non_admins_cannot_view_the_admin_directories(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 1]);

        $this->actingAs($teacher)->get(route('admin.teachers'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.students'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.teachers'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students'))->assertForbidden();
    }
}
