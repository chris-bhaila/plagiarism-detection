<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'faculty' => 'BCA',
            'semester' => 3,
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertSame('BCA', $user->faculty);
        $this->assertSame(3, $user->semester);

        $response->assertRedirect(route($user->homeRouteName(), absolute: false));
    }

    public function test_registration_requires_a_valid_faculty_and_semester(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'faculty' => 'NOT_A_REAL_FACULTY',
            'semester' => 9,
        ]);

        $response->assertSessionHasErrors(['faculty', 'semester']);
        $this->assertGuest();
    }
}
