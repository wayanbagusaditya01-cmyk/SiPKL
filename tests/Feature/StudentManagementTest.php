<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_coordinator_sees_student_profiles_without_other_roles(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        Student::factory()->create([
            'nis' => '2026001',
            'name' => 'Murid Profil',
            'class' => 'XII RPL 1',
            'major' => 'Rekayasa Perangkat Lunak',
        ]);
        User::factory()->create(['name' => 'Guru Jangan Tampil', 'role' => 'pembina']);
        User::factory()->create(['name' => 'Admin Jangan Tampil', 'role' => 'admin']);

        $this->actingAs($coordinator)
            ->get(route('coordinator.siswa.index'))
            ->assertOk()
            ->assertSee('Murid Profil')
            ->assertDontSee('Guru Jangan Tampil')
            ->assertDontSee('Admin Jangan Tampil');
    }

    public function test_guest_is_redirected_from_student_directory(): void
    {
        $this->get(route('coordinator.siswa.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_coordinator_cannot_manage_student_profiles(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->get(route('coordinator.siswa.index'))
            ->assertForbidden();
    }

    public function test_coordinator_can_create_student_profile_without_login_account(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->post(route('coordinator.siswa.store'), [
                'nis' => '2026002',
                'name' => 'Siswa Tanpa Akun',
                'class' => 'XII TKJ 1',
                'major' => 'Teknik Komputer dan Jaringan',
                'email' => 'kontak@sekolah.test',
                'user_id' => '',
            ])
            ->assertRedirect(route('coordinator.siswa.index'));

        $this->assertDatabaseHas('students', [
            'nis' => '2026002',
            'name' => 'Siswa Tanpa Akun',
            'user_id' => null,
        ]);
    }

    public function test_coordinator_can_link_an_active_student_account(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $studentAccount = User::factory()->create(['role' => 'siswa', 'status' => true]);

        $this->actingAs($coordinator)
            ->post(route('coordinator.siswa.store'), [
                'nis' => '2026003',
                'name' => 'Siswa Terhubung',
                'class' => 'XII RPL 2',
                'major' => 'Rekayasa Perangkat Lunak',
                'email' => 'kontak2@sekolah.test',
                'user_id' => $studentAccount->id,
            ])
            ->assertRedirect(route('coordinator.siswa.index'));

        $this->assertDatabaseHas('students', [
            'nis' => '2026003',
            'user_id' => $studentAccount->id,
        ]);
    }

    public function test_linked_student_sees_own_placement_instead_of_preview_place(): void
    {
        $studentAccount = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $studentAccount->id]);
        $place = Place::factory()->create(['name' => 'PT Tempat Milik Siswa']);
        $student->placement()->create(['place_id' => $place->id, 'placed_at' => '2026-08-10']);

        $this->actingAs($studentAccount)
            ->get(route('preview.page', ['role' => 'siswa', 'page' => 'dashboard']))
            ->assertRedirect(route('student.portal.dashboard'));

        $this->get(route('student.portal.dashboard'))
            ->assertOk()
            ->assertSee('PT Tempat Milik Siswa')
            ->assertSee('10-08-2026')
            ->assertDontSee('Studio Nusa Digital');

        $this->get(route('preview.page', ['role' => 'siswa', 'page' => 'penempatan']))
            ->assertRedirect(route('student.portal.placement'));

        $this->get(route('student.portal.placement'))
            ->assertOk()
            ->assertSee('PT Tempat Milik Siswa')
            ->assertDontSee('Studio Nusa Digital');
    }

    public function test_linked_student_without_placement_does_not_see_a_preview_place(): void
    {
        $studentAccount = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $studentAccount->id]);

        $this->actingAs($studentAccount)
            ->get(route('preview.page', ['role' => 'siswa', 'page' => 'dashboard']))
            ->assertRedirect(route('student.portal.dashboard'));

        $this->get(route('student.portal.dashboard'))
            ->assertOk()
            ->assertSee('Penempatan belum ditetapkan')
            ->assertDontSee('Studio Nusa Digital');
    }

    public function test_coordinator_cannot_link_a_teacher_account(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $teacher = User::factory()->create(['role' => 'pembina']);

        $this->actingAs($coordinator)
            ->from(route('coordinator.siswa.create'))
            ->post(route('coordinator.siswa.store'), [
                'nis' => '2026004',
                'name' => 'Siswa Validasi',
                'class' => 'XII TKJ 1',
                'major' => 'Teknik Komputer dan Jaringan',
                'user_id' => $teacher->id,
            ])
            ->assertRedirect(route('coordinator.siswa.create'))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('students', ['nis' => '2026004']);
    }

    public function test_coordinator_student_forms_render(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create(['nis' => '2026005', 'name' => 'Siswa Form']);

        $this->actingAs($coordinator)
            ->get(route('coordinator.siswa.create'))
            ->assertOk()
            ->assertSee('Tambah Siswa');

        $this->get(route('coordinator.siswa.edit', $student))
            ->assertOk()
            ->assertSee('Ubah Data Siswa')
            ->assertSee('Siswa Form');
    }

    public function test_coordinator_dashboard_links_to_database_student_directory(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->get(route('preview.page', ['role' => 'koordinator', 'page' => 'dashboard']))
            ->assertOk()
            ->assertSee(route('coordinator.siswa.index'), false);
    }
}
