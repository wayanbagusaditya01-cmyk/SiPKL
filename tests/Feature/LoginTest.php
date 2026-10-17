<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_login_form_has_no_public_registration_route(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk ke SIPKL')
            ->assertSee('action="'.route('login.store').'"', false);

        $this->get('/register')->assertNotFound();
    }

    public function test_student_login_redirects_to_student_dashboard(): void
    {
        $student = User::factory()->create(['email' => 'murid@sekolah.test', 'password' => 'rahasia', 'role' => 'siswa']);

        $this->post(route('login.store'), ['email' => $student->email, 'password' => 'rahasia'])
            ->assertRedirect(route('preview.page', ['role' => 'siswa', 'page' => 'dashboard']));

        $this->assertAuthenticatedAs($student);
    }

    public function test_teacher_login_redirects_to_teacher_dashboard(): void
    {
        $teacher = User::factory()->create(['email' => 'guru@sekolah.test', 'password' => 'rahasia', 'role' => 'pembina']);

        $this->post(route('login.store'), ['email' => $teacher->email, 'password' => 'rahasia'])
            ->assertRedirect(route('preview.page', ['role' => 'pembina', 'page' => 'dashboard']));

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_coordinator_login_redirects_to_coordinator_dashboard(): void
    {
        $coordinator = User::factory()->create(['email' => 'koordinator@sekolah.test', 'password' => 'rahasia', 'role' => 'koordinator']);

        $this->post(route('login.store'), ['email' => $coordinator->email, 'password' => 'rahasia'])
            ->assertRedirect(route('coordinator.dashboard'));

        $this->assertAuthenticatedAs($coordinator);

        $this->get(route('login'))
            ->assertRedirect(route('coordinator.dashboard'));
    }

    public function test_invalid_credentials_and_inactive_accounts_cannot_login(): void
    {
        $student = User::factory()->create(['email' => 'murid@sekolah.test', 'password' => 'rahasia']);

        $this->from(route('login'))->post(route('login.store'), ['email' => $student->email, 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $student->update(['status' => false]);

        $this->from(route('login'))->post(route('login.store'), ['email' => $student->email, 'password' => 'rahasia'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_cannot_open_another_roles_ui_or_access_preview_as_guest(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->get('/preview/siswa/dashboard')->assertRedirect(route('login'));

        $this->actingAs($student)->get('/preview/siswa/dashboard')->assertOk();
        $this->get('/preview/pembina/dashboard')->assertForbidden();
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/preview/siswa/dashboard')->assertRedirect(route('login'));
    }
}
