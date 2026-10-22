<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_admin_can_filter_accounts_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Murid Uji', 'email' => 'murid@sekolah.test', 'role' => 'siswa']);
        User::factory()->create(['name' => 'Guru Uji', 'email' => 'guru@sekolah.test', 'role' => 'pembina']);

        $this->actingAs($admin)
            ->get(route('admin.accounts.index', ['role' => 'siswa']))
            ->assertOk()
            ->assertSee('Murid Uji')
            ->assertDontSee('Guru Uji');
    }

    public function test_admin_dashboard_links_to_database_account_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('preview.page', ['role' => 'admin', 'page' => 'dashboard']))
            ->assertOk()
            ->assertSee(route('admin.accounts.index'), false);
    }

    public function test_account_forms_render_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)
            ->get(route('admin.accounts.create'))
            ->assertOk()
            ->assertSee('Buat Akun')
            ->assertSee('Peran akun');

        $this->get(route('admin.accounts.edit', $student))
            ->assertOk()
            ->assertSee('Ubah Akun')
            ->assertSee($student->email);
    }

    public function test_guest_is_redirected_from_account_management(): void
    {
        $this->get(route('admin.accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_open_account_management(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->get(route('admin.accounts.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_database_account_with_a_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.accounts.store'), [
                'name' => 'Murid Baru',
                'email' => 'murid.baru@sekolah.test',
                'password' => 'Password-Sekolah-2026!',
                'password_confirmation' => 'Password-Sekolah-2026!',
                'role' => 'siswa',
                'status' => '1',
            ])
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Murid Baru',
            'email' => 'murid.baru@sekolah.test',
            'role' => 'siswa',
            'status' => true,
        ]);
        $this->assertTrue(Hash::check('Password-Sekolah-2026!', User::where('email', 'murid.baru@sekolah.test')->firstOrFail()->password));
    }

    public function test_admin_cannot_create_an_account_with_an_unknown_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.accounts.create'))
            ->post(route('admin.accounts.store'), [
                'name' => 'Akun Tak Valid',
                'email' => 'tak.valid@sekolah.test',
                'password' => 'Password-Sekolah-2026!',
                'password_confirmation' => 'Password-Sekolah-2026!',
                'role' => 'superadmin',
                'status' => '1',
            ])
            ->assertRedirect(route('admin.accounts.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'tak.valid@sekolah.test']);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.accounts.edit', $admin))
            ->put(route('admin.accounts.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
                'status' => '0',
            ])
            ->assertRedirect(route('admin.accounts.edit', $admin))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => true]);
    }

    public function test_admin_can_change_another_accounts_role_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)
            ->put(route('admin.accounts.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'role' => 'pembina',
                'status' => '0',
            ])
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'pembina',
            'status' => false,
        ]);
    }
}
