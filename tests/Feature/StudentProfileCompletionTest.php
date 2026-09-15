<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_without_a_semester_is_redirected_to_complete_their_profile(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => null]);

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertRedirect(route('profile.complete'));
    }

    public function test_the_complete_profile_page_itself_is_reachable_without_a_redirect_loop(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => null]);

        $this->actingAs($student)
            ->get(route('profile.complete'))
            ->assertOk();
    }

    public function test_submitting_the_form_completes_the_profile_and_unblocks_the_student(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => null]);
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BIM'])->semesters()->where('number', 6)->first();

        $response = $this->actingAs($student)->post(route('profile.complete.store'), [
            'semester_id' => $semester->id,
        ]);

        $response->assertRedirect(route('assignments.index'));

        $student->refresh();
        $this->assertSame($semester->id, $student->semester_id);

        // No longer blocked now that the profile is complete.
        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertOk();
    }

    public function test_a_student_with_a_complete_profile_is_not_redirected(): void
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertOk();
    }

    public function test_teachers_and_admins_are_never_gated(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'semester_id' => null]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'semester_id' => null]);

        $this->actingAs($teacher)->get(route('courses.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_a_student_who_already_completed_their_profile_cannot_revisit_the_form(): void
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $this->actingAs($student)
            ->get(route('profile.complete'))
            ->assertRedirect(route('assignments.index'));
    }

    public function test_a_student_who_already_completed_their_profile_cannot_resubmit_to_change_it(): void
    {
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $otherSemester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        $response = $this->actingAs($student)->post(route('profile.complete.store'), [
            'semester_id' => $otherSemester->id,
        ]);

        $response->assertForbidden();

        $student->refresh();
        $this->assertSame($semester->id, $student->semester_id);
    }
}
