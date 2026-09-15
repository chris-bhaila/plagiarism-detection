<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_shows_a_students_faculty_and_semester(): void
    {
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BIM'])->semesters()->where('number', 5)->first();

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'semester_id' => $semester->id,
        ]);

        $response = $this->actingAs($student)->get('/profile');

        $response->assertOk();
        $response->assertSeeText('BIM');
        $response->assertSeeText('5');
    }

    public function test_faculty_and_semester_cannot_be_changed_via_the_profile_form(): void
    {
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BCA'])->semesters()->where('number', 1)->first();
        $otherSemester = Faculty::factory()->withSemesters()->create(['name' => 'BBM'])->semesters()->where('number', 7)->first();

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'semester_id' => $semester->id,
        ]);

        $response = $this->actingAs($student)->patch('/profile', [
            'name' => $student->name,
            'email' => $student->email,
            'semester_id' => $otherSemester->id,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        // The submitted semester_id is silently ignored, not applied.
        $student->refresh();
        $this->assertSame($semester->id, $student->semester_id);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
