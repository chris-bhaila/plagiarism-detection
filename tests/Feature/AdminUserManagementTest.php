<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_a_students_name_email_and_faculty_semester(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Old Name',
            'faculty' => 'BCA',
            'semester' => 1,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $student), [
            'name' => 'New Name',
            'email' => $student->email,
            'role' => User::ROLE_STUDENT,
            'faculty' => 'BBM',
            'semester' => 6,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.edit', $student));

        $student->refresh();
        $this->assertSame('New Name', $student->name);
        $this->assertSame('BBM', $student->faculty);
        $this->assertSame(6, $student->semester);
    }

    public function test_faculty_and_semester_are_cleared_when_role_changes_away_from_student(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'faculty' => 'BCA',
            'semester' => 1,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $student), [
            'name' => $student->name,
            'email' => $student->email,
            'role' => User::ROLE_TEACHER,
        ]);

        $student->refresh();
        $this->assertSame(User::ROLE_TEACHER, $student->role);
        $this->assertNull($student->faculty);
        $this->assertNull($student->semester);
    }

    public function test_updating_a_student_requires_faculty_and_semester(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 1]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $student), [
            'name' => $student->name,
            'email' => $student->email,
            'role' => User::ROLE_STUDENT,
            'faculty' => '',
            'semester' => '',
        ]);

        $response->assertSessionHasErrors(['faculty', 'semester']);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_TEACHER,
        ]);

        $response->assertForbidden();
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_admin_can_disable_and_re_enable_a_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($admin)->post(route('admin.users.toggle-disabled', $teacher))
            ->assertRedirect(route('admin.users.edit', $teacher));
        $this->assertTrue($teacher->fresh()->isDisabled());

        $this->actingAs($admin)->post(route('admin.users.toggle-disabled', $teacher))
            ->assertRedirect(route('admin.users.edit', $teacher));
        $this->assertFalse($teacher->fresh()->isDisabled());
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-disabled', $admin))
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->isDisabled());
    }

    public function test_a_disabled_user_cannot_log_in(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['disabled_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_user_disabled_mid_session_is_logged_out_on_their_next_request(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 1]);

        // Confirm the session is live before disabling.
        $this->actingAs($student)->get(route('assignments.index'))->assertOk();

        $student->forceFill(['disabled_at' => now()])->save();

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_non_admins_cannot_edit_or_disable_users(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherUser = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 1]);

        $this->actingAs($teacher)->get(route('admin.users.edit', $otherUser))->assertForbidden();
        $this->actingAs($teacher)->patch(route('admin.users.update', $otherUser), ['name' => 'x'])->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.users.toggle-disabled', $otherUser))->assertForbidden();
    }
}
