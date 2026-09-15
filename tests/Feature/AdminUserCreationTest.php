<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_teacher_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Teacher',
            'email' => 'newteacher@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_TEACHER,
        ]);

        $response->assertRedirect(route('admin.teachers'));

        $user = User::where('email', 'newteacher@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_TEACHER, $user->role);
        $this->assertNull($user->semester_id);
    }

    public function test_admin_can_create_a_student_account_with_a_semester(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_STUDENT,
            'semester_id' => $semester->id,
        ]);

        $response->assertRedirect(route('admin.students'));

        $user = User::where('email', 'newstudent@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertSame($semester->id, $user->semester_id);
    }

    public function test_creating_a_student_without_a_semester_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $response->assertSessionHasErrors(['semester_id']);
        $this->assertDatabaseMissing('users', ['email' => 'newstudent@example.com']);
    }

    public function test_admin_role_cannot_be_chosen_when_creating_an_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_a_teacher_cannot_create_accounts(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.users.store'), [
            'name' => 'New Teacher',
            'email' => 'newteacher@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_TEACHER,
        ])->assertForbidden();
    }
}
