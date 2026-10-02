<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_guest_sees_login_page(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('login-page'));
    }

    public function test_root_redirects_guest_to_login_and_user_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');

        $this->actingAsGuru();
        $this->get('/dashboard')->assertOk();
    }

    public function test_authenticated_user_visiting_login_is_redirected_to_dashboard(): void
    {
        $this->actingAsGuru();

        $this->get('/login')->assertRedirect('/dashboard');
    }

    public function test_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['username' => 'rio.falentino', 'password' => 'guru12345']);

        $this->post('/login', ['username' => 'rio.falentino', 'password' => 'guru12345'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['username' => 'rio', 'password' => 'guru12345']);

        $this->from('/login')->post('/login', ['username' => 'rio', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Username atau password salah']);
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_username_using_the_same_message(): void
    {
        $this->from('/login')->post('/login', ['username' => 'tidak-ada', 'password' => 'apa-saja'])
            ->assertSessionHasErrors(['username' => 'Username atau password salah']);
        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        User::factory()->nonaktif()->create(['username' => 'rio', 'password' => 'guru12345']);

        $this->from('/login')->post('/login', ['username' => 'rio', 'password' => 'guru12345'])
            ->assertSessionHasErrors(['username' => 'Akun dinonaktifkan. Hubungi admin sekolah.']);
        $this->assertGuest();
    }

    public function test_account_deactivated_while_logged_in_is_kicked_out_on_next_request(): void
    {
        $user = $this->actingAsGuru();
        $this->get('/dashboard')->assertOk();

        $user->update(['aktif' => false]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_five_failed_attempts_lock_the_username_for_a_minute(): void
    {
        User::factory()->create(['username' => 'rio', 'password' => 'guru12345']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['username' => 'rio', 'password' => 'salah'])
                ->assertSessionHasErrors(['username' => 'Username atau password salah']);
        }

        // Sandi yang benar pun ditolak selama terkunci.
        $this->from('/login')->post('/login', ['username' => 'rio', 'password' => 'guru12345'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAsGuru();

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_pages_are_not_cacheable_by_the_browser_history(): void
    {
        $this->actingAsGuru();

        $cache = $this->get('/dashboard')->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cache);
    }
}
