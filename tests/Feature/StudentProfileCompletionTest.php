<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_without_faculty_or_semester_is_redirected_to_complete_their_profile(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => null, 'semester' => null]);

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertRedirect(route('profile.complete'));
    }

    public function test_the_complete_profile_page_itself_is_reachable_without_a_redirect_loop(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => null, 'semester' => null]);

        $this->actingAs($student)
            ->get(route('profile.complete'))
            ->assertOk();
    }

    public function test_submitting_the_form_completes_the_profile_and_unblocks_the_student(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => null, 'semester' => null]);

        $response = $this->actingAs($student)->post(route('profile.complete.store'), [
            'faculty' => 'BIM',
            'semester' => 6,
        ]);

        $response->assertRedirect(route('assignments.index'));

        $student->refresh();
        $this->assertSame('BIM', $student->faculty);
        $this->assertSame(6, $student->semester);

        // No longer blocked now that the profile is complete.
        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertOk();
    }

    public function test_a_student_with_a_complete_profile_is_not_redirected(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 2]);

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertOk();
    }

    public function test_teachers_and_admins_are_never_gated(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'faculty' => null, 'semester' => null]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'faculty' => null, 'semester' => null]);

        $this->actingAs($teacher)->get(route('courses.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_a_student_who_already_completed_their_profile_cannot_revisit_the_form(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 2]);

        $this->actingAs($student)
            ->get(route('profile.complete'))
            ->assertRedirect(route('assignments.index'));
    }

    public function test_a_student_who_already_completed_their_profile_cannot_resubmit_to_change_it(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'faculty' => 'BCA', 'semester' => 2]);

        $response = $this->actingAs($student)->post(route('profile.complete.store'), [
            'faculty' => 'BBM',
            'semester' => 8,
        ]);

        $response->assertForbidden();

        $student->refresh();
        $this->assertSame('BCA', $student->faculty);
        $this->assertSame(2, $student->semester);
    }
}
