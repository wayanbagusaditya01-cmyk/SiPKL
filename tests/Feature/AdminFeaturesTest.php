<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\SystemReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_admin_dashboard_renders_metrics_and_unlinked_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Utama']);
        $studentUser = User::factory()->create(['role' => 'siswa', 'name' => 'Siswa Unlinked']);
        // Student user without linked student record in database

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Sistem')
            ->assertSee('Total akun')
            ->assertSee('Siswa Unlinked');
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_view_account_details_with_profile_and_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'siswa', 'name' => 'Siswa Detail', 'email' => 'siswa.detail@sekolah.test']);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'nis' => '8888',
            'name' => 'Siswa Detail',
            'class' => 'XII RPL 2',
        ]);

        ActivityLog::record('Uji Aksi', 'Entitas Uji', 'Detail aktivitas uji', $studentUser);

        $this->actingAs($admin)
            ->get(route('admin.accounts.show', $studentUser))
            ->assertOk()
            ->assertSee('Detail Akun: Siswa Detail')
            ->assertSee('8888')
            ->assertSee('XII RPL 2')
            ->assertSee('Uji Aksi');
    }

    public function test_admin_can_toggle_account_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'siswa', 'status' => true]);

        $this->actingAs($admin)
            ->post(route('admin.accounts.toggle-status', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Nonaktivasi Akun',
        ]);

        // Toggle back to active
        $this->actingAs($admin)
            ->post(route('admin.accounts.toggle-status', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => true,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account_via_toggle(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => true]);

        $this->actingAs($admin)
            ->post(route('admin.accounts.toggle-status', $admin))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'status' => true,
        ]);
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)
            ->post(route('admin.accounts.reset-password', $user), [
                'password' => 'PasswordBaru123456!',
                'password_confirmation' => 'PasswordBaru123456!',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('PasswordBaru123456!', $user->password));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Reset Kata Sandi',
        ]);
    }

    public function test_admin_can_delete_another_user_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'siswa', 'email' => 'hapus.saya@sekolah.test']);

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $user))
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseMissing('users', ['email' => 'hapus.saya@sekolah.test']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Hapus Akun',
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $admin))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_view_and_update_permissions_matrix(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('Hak Akses')
            ->assertSee('Akun &amp; akses', false);

        $this->actingAs($admin)
            ->put(route('admin.permissions.update'), [
                'permissions' => [
                    'operational' => [
                        'koordinator' => 'Kelola Penuh',
                        'pembina' => 'Baca bimbingan',
                        'siswa' => 'Baca milik sendiri',
                        'admin' => 'Tidak tersedia',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.permissions.index'));

        $this->assertDatabaseHas('role_permissions', [
            'module_key' => 'operational',
            'role' => 'koordinator',
            'access_level' => 'Kelola Penuh',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Ubah Hak Akses',
        ]);
    }

    public function test_admin_can_view_and_filter_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Penguji']);
        ActivityLog::record('Aksi Satu', 'Entitas Alpha Unik', 'Detail satu', $admin);
        ActivityLog::record('Aksi Dua', 'Entitas Beta Unik', 'Detail dua', $admin);

        $this->actingAs($admin)
            ->get(route('admin.logs.index', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee('Entitas Alpha Unik')
            ->assertDontSee('Entitas Beta Unik');
    }

    public function test_admin_can_clear_old_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldLog = ActivityLog::record('Aksi Lama', 'Entitas Lama', 'Detail lama', $admin);
        $oldLog->created_at = now()->subDays(60);
        $oldLog->save();

        $this->actingAs($admin)
            ->delete(route('admin.logs.clear', ['days' => 30]))
            ->assertRedirect(route('admin.logs.index'));

        $this->assertDatabaseMissing('activity_logs', ['action' => 'Aksi Lama']);
    }

    public function test_admin_can_view_and_manage_master_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.master-data.index'))
            ->assertOk()
            ->assertSee('Master Data Sistem')
            ->assertSee('Peran akun');

        // Store custom reference
        $this->actingAs($admin)
            ->post(route('admin.master-data.store'), [
                'category' => 'Jurusan',
                'code' => 'animasi',
                'name' => 'Animasi 3D',
                'description' => 'Kompetensi animasi tiga dimensi',
            ])
            ->assertRedirect(route('admin.master-data.index', ['category' => 'Jurusan']));

        $this->assertDatabaseHas('system_references', [
            'category' => 'Jurusan',
            'code' => 'animasi',
            'name' => 'Animasi 3D',
            'is_system' => false,
        ]);

        $ref = SystemReference::where('code', 'animasi')->firstOrFail();

        // Update reference
        $this->actingAs($admin)
            ->put(route('admin.master-data.update', $ref), [
                'name' => 'Animasi dan Multimedia',
                'description' => 'Jurusan gabungan animasi dan multimedia',
            ])
            ->assertRedirect(route('admin.master-data.index', ['category' => 'Jurusan']));

        $this->assertDatabaseHas('system_references', [
            'id' => $ref->id,
            'name' => 'Animasi dan Multimedia',
        ]);

        // Delete custom reference
        $this->actingAs($admin)
            ->delete(route('admin.master-data.destroy', $ref))
            ->assertRedirect(route('admin.master-data.index', ['category' => 'Jurusan']));

        $this->assertDatabaseMissing('system_references', ['id' => $ref->id]);
    }

    public function test_admin_cannot_delete_system_master_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemReference::ensureDefaults();
        $systemRef = SystemReference::where('is_system', true)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.master-data.destroy', $systemRef))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('system_references', ['id' => $systemRef->id]);
    }

    public function test_admin_can_view_system_reports_and_export_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Laporan Penggunaan Sistem')
            ->assertSee('Status Akun per Peran');

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export-csv'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_update_own_profile_and_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Awal',
            'email' => 'admin.awal@sekolah.test',
            'password' => Hash::make('PasswordLama123!'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Profil Admin')
            ->assertSee('admin.awal@sekolah.test');

        // Update profile
        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => 'Admin Baru',
                'email' => 'admin.baru@sekolah.test',
                'phone' => '081234567890',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Baru',
            'email' => 'admin.baru@sekolah.test',
            'phone' => '081234567890',
        ]);

        // Update password
        $this->actingAs($admin)
            ->put(route('admin.profile.password'), [
                'current_password' => 'PasswordLama123!',
                'password' => 'PasswordBaru123456!',
                'password_confirmation' => 'PasswordBaru123456!',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $admin->refresh();
        $this->assertTrue(Hash::check('PasswordBaru123456!', $admin->password));
    }
}
