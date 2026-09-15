<?php

namespace Tests\Feature\Auth;

use App\Models\Faculty;
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
        $semester = Faculty::factory()->withSemesters()->create(['name' => 'BCA'])->semesters()->where('number', 3)->first();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'semester_id' => $semester->id,
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertSame($semester->id, $user->semester_id);

        $response->assertRedirect(route($user->homeRouteName(), absolute: false));
    }

    public function test_registration_requires_a_valid_semester(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'semester_id' => 99999,
        ]);

        $response->assertSessionHasErrors(['semester_id']);
        $this->assertGuest();
    }
}
