<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFacultyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_faculty_with_8_semesters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.faculties.store'), ['name' => 'BCA']);

        $response->assertRedirect(route('admin.faculties.index'));

        $faculty = Faculty::where('name', 'BCA')->firstOrFail();
        $this->assertSame(8, $faculty->semesters()->count());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $faculty->semesters()->orderBy('number')->pluck('number')->all());
    }

    public function test_admin_can_view_a_faculties_semesters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Taylor Teacher']);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $semester = $faculty->semesters()->where('number', 3)->first();
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id, 'name' => 'Data Structures']);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($admin)->get(route('admin.faculties.show', $faculty));

        $response->assertOk();
        $response->assertSeeText('Semester 3');
        // Full course/teacher/student detail lives on the semester's own
        // page (AdminSemesterManagementTest) — this page just links there.
        $response->assertSee(route('admin.semesters.show', $semester), false);
    }

    public function test_a_teacher_cannot_manage_faculties(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $faculty = Faculty::factory()->withSemesters()->create();

        $this->actingAs($teacher)->get(route('admin.faculties.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.faculties.store'), ['name' => 'BCA'])->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.faculties.show', $faculty))->assertForbidden();
    }

    public function test_deleting_an_empty_faculty_succeeds(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);

        $response = $this->actingAs($admin)->delete(route('admin.faculties.destroy', $faculty));

        $response->assertRedirect(route('admin.faculties.index'));
        $this->assertDatabaseMissing('faculties', ['id' => $faculty->id]);
    }

    public function test_deleting_a_faculty_with_students_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $semester = $faculty->semesters()->first();
        User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $faculty))
            ->assertStatus(422);

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_deleting_a_faculty_with_courses_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $semester = $faculty->semesters()->first();
        Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);

        $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $faculty))
            ->assertStatus(422);

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_deleting_one_faculty_does_not_affect_another_with_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $empty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);
        $inUse = Faculty::factory()->withSemesters()->create(['name' => 'BIM']);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $inUse->semesters()->first()->id]);

        $this->actingAs($admin)
            ->delete(route('admin.faculties.destroy', $empty))
            ->assertRedirect(route('admin.faculties.index'));

        $this->assertDatabaseMissing('faculties', ['id' => $empty->id]);
        $this->assertDatabaseHas('faculties', ['id' => $inUse->id]);
    }

    public function test_admin_can_rename_a_faculty(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);

        $response = $this->actingAs($admin)->patch(route('admin.faculties.update', $faculty), ['name' => 'BCA (renamed)']);

        $response->assertRedirect(route('admin.faculties.show', $faculty));
        $this->assertSame('BCA (renamed)', $faculty->fresh()->name);
    }

    public function test_renaming_a_faculty_to_an_existing_name_fails(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Faculty::factory()->withSemesters()->create(['name' => 'BIM']);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);

        $response = $this->actingAs($admin)->patch(route('admin.faculties.update', $faculty), ['name' => 'BIM']);

        $response->assertSessionHasErrors(['name']);
        $this->assertSame('BCA', $faculty->fresh()->name);
    }

    public function test_a_teacher_cannot_rename_a_faculty(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $faculty = Faculty::factory()->withSemesters()->create(['name' => 'BCA']);

        $this->actingAs($teacher)->patch(route('admin.faculties.update', $faculty), ['name' => 'X'])->assertForbidden();
    }
}
