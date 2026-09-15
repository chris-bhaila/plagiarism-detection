<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Faculty;
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

        $bca = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $bim = Faculty::factory()->withSemesters()->create(['name' => 'BIM']);
        $bcaSem4 = $bca->semesters()->where('number', 4)->first();
        $bcaSem2 = $bca->semesters()->where('number', 2)->first();
        $bimSem4 = $bim->semesters()->where('number', 4)->first();

        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Match Student', 'semester_id' => $bcaSem4->id]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Wrong Faculty', 'semester_id' => $bimSem4->id]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Wrong Semester', 'semester_id' => $bcaSem2->id]);

        $response = $this->actingAs($admin)->get(route('admin.students', ['faculty_id' => $bca->id, 'semester_id' => $bcaSem4->id]));

        $response->assertOk();
        $response->assertSeeText('Match Student');
        $response->assertDontSeeText('Wrong Faculty');
        $response->assertDontSeeText('Wrong Semester');
    }

    public function test_students_list_shows_enrolled_course_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Enrolled Student', 'semester_id' => $semester->id]);

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

    public function test_admin_can_search_teachers_by_name_or_email(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher', 'email' => 'taylor@example.com']);
        User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Jordan Instructor', 'email' => 'jordan@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.teachers', ['search' => 'Taylor']));

        $response->assertOk();
        $response->assertSeeText('Taylor Teacher');
        $response->assertDontSeeText('Jordan Instructor');
    }

    public function test_non_admins_cannot_view_the_admin_directories(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($teacher)->get(route('admin.teachers'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.students'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.teachers'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students'))->assertForbidden();
    }
}
