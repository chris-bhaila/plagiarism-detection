<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_shows_a_students_faculty_and_semester(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'faculty' => 'BIM',
            'semester' => 5,
        ]);

        $response = $this->actingAs($student)->get('/profile');

        $response->assertOk();
        $response->assertSeeText('BIM');
        $response->assertSeeText('5');
    }

    public function test_faculty_and_semester_cannot_be_changed_via_the_profile_form(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'faculty' => 'BCA',
            'semester' => 1,
        ]);

        $response = $this->actingAs($student)->patch('/profile', [
            'name' => $student->name,
            'email' => $student->email,
            'faculty' => 'BBM',
            'semester' => 7,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        // The submitted faculty/semester are silently ignored, not applied.
        $student->refresh();
        $this->assertSame('BCA', $student->faculty);
        $this->assertSame(1, $student->semester);
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
