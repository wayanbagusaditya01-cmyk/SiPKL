<?php

namespace Tests\Feature;

use App\Models\Mentor;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Support\PreviewData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    private function signInAs(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
    }

    public function test_every_role_page_and_create_form_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Masuk ke SIPKL');
        foreach (config('preview.roles') as $role => $identity) {
            $this->signInAs($role);

            foreach ($identity['menus'] as $page => $label) {
                $this->get("/preview/$role/$page")->assertOk()->assertSee($label)->assertSee('Data contoh');
            }
        }

        $this->signInAs('koordinator');
        foreach (['siswa', 'tempat', 'pembina', 'periode', 'penempatan'] as $page) {
            $this->get("/preview/koordinator/$page?form=new")->assertOk();
        }

        $this->signInAs('admin');
        $this->get('/preview/admin/akun?form=new')->assertOk();
        $this->get('/preview/admin/akun?form=1')->assertOk();
    }

    public function test_preview_is_disabled_by_default_and_production_remains_closed(): void
    {
        $this->signInAs('siswa');
        config(['preview.enabled' => false]);
        $this->get('/preview/siswa/dashboard')->assertNotFound();
        config(['preview.enabled' => true]);
        $this->app->instance('env', 'production');
        $this->get('/preview/siswa/dashboard')->assertNotFound();
    }

    public function test_invalid_role_pages_and_outside_scope_details_are_rejected(): void
    {
        $this->signInAs('siswa');
        $this->get('/preview/siswa/akun')->assertNotFound();
        $this->get('/preview/unknown/dashboard')->assertForbidden();
        $this->get('/preview/admin/dashboard')->assertForbidden();

        $this->signInAs('pembina');
        $this->get('/preview/pembina/siswa?detail=3')->assertNotFound();

        $this->signInAs('siswa');
        $this->get('/preview/siswa/jurnal?journal=2')->assertNotFound();

        $this->post('/preview/siswa/nilai', ['action' => 'publish'])->assertForbidden();
    }

    public function test_attendance_rejects_duplicates_outside_period_and_reversed_hours(): void
    {
        $this->signInAs('siswa');
        $payload = ['action' => 'attendance', 'date' => '2026-10-07', 'status' => 'Hadir', 'in' => '08:00', 'out' => '16:00'];
        $this->post('/preview/siswa/absensi', $payload)->assertSessionHasErrors('date');
        $this->post('/preview/siswa/absensi', array_replace($payload, ['date' => '2026-11-01']))->assertSessionHasErrors('date');
        $this->post('/preview/siswa/absensi', array_replace($payload, ['date' => '2026-10-08', 'out' => '07:00']))->assertSessionHasErrors('out');
        $this->post('/preview/siswa/absensi', array_replace($payload, ['date' => '2026-10-08']))->assertSessionHasNoErrors()->assertRedirect('/preview/siswa/absensi');
        $this->get('/preview/siswa/dashboard')->assertSee('Absensi hari ini sudah diisi.');
    }

    public function test_revisions_need_notes_and_student_can_resubmit(): void
    {
        $this->signInAs('pembina');
        $this->post('/preview/pembina/jurnal', ['action' => 'revise', 'id' => 1, 'note' => ''])->assertSessionHasErrors('note');
        $this->post('/preview/pembina/jurnal', ['action' => 'revise', 'id' => 1, 'note' => 'Tambahkan hasil uji.'])->assertSessionHasNoErrors();

        $this->signInAs('siswa');
        $this->get('/preview/siswa/jurnal?journal=1')->assertSee('Tambahkan hasil uji.')->assertSee('Kirim Ulang Jurnal');
        $this->post('/preview/siswa/jurnal', ['action' => 'send', 'id' => 1, 'date' => '2026-10-08', 'title' => 'Pengujian selesai', 'activity' => 'Hasil uji dan pembelajaran sudah dilengkapi.'])->assertSessionHasNoErrors();

        $this->signInAs('pembina');
        $this->get('/preview/pembina/jurnal?journal=1')->assertSee('Pengujian selesai')->assertSee('Menunggu');
    }

    public function test_unsafe_documentation_url_and_edits_to_approved_journal_are_rejected(): void
    {
        $this->signInAs('siswa');
        $payload = ['action' => 'send', 'date' => '2026-10-08', 'title' => 'Contoh', 'activity' => 'Kegiatan contoh', 'url' => 'javascript:alert(1)'];
        $this->post('/preview/siswa/jurnal', $payload)->assertSessionHasErrors('url');
        $payload['url'] = '';
        $payload['id'] = 4;
        $this->post('/preview/siswa/jurnal', $payload)->assertForbidden();
    }

    public function test_grade_draft_is_hidden_and_published_grade_becomes_visible(): void
    {
        $this->signInAs('pembina');
        $payload = ['action' => 'draft', 'student' => 1, 'grade' => '87.25', 'note' => 'Catatan rahasia draf contoh'];
        $this->post('/preview/pembina/penilaian', $payload)->assertSessionHasNoErrors();
        $this->signInAs('siswa');
        $this->get('/preview/siswa/nilai')->assertSee('Nilai belum diterbitkan')->assertDontSee('87.25')->assertDontSee('Catatan rahasia draf contoh');
        $this->get('/preview/siswa/dashboard')->assertDontSee('87.25');

        $this->signInAs('pembina');
        $this->post('/preview/pembina/penilaian', array_replace($payload, ['action' => 'publish']))->assertSessionHasNoErrors();

        $this->signInAs('siswa');
        $this->get('/preview/siswa/nilai')->assertSee('87.25')->assertSee('Catatan rahasia draf contoh');

        $this->signInAs('pembina');
        $this->post('/preview/pembina/penilaian', array_replace($payload, ['grade' => 101]))->assertSessionHasErrors('grade');
        $this->post('/preview/pembina/penilaian', array_replace($payload, ['student' => 3]))->assertForbidden();
    }

    public function test_placement_requires_matching_mentor_and_never_duplicates_students(): void
    {
        $this->signInAs('koordinator');
        $payload = ['action' => 'save', 'student' => '3', 'place' => '2', 'period' => '1', 'school' => '5', 'industry' => '6', 'date' => '2026-10-08'];
        $this->post('/preview/koordinator/penempatan', $payload)->assertSessionHasErrors('industry');
        $this->post('/preview/koordinator/penempatan', array_replace($payload, ['student' => '1', 'place' => '1']))->assertSessionHasErrors('student');
        $this->post('/preview/koordinator/penempatan', array_replace($payload, ['place' => '1']))->assertSessionHasNoErrors();
        $this->post('/preview/koordinator/penempatan', array_replace($payload, ['place' => '1']))->assertSessionHasErrors('student');
    }

    public function test_incomplete_accounts_cannot_activate_and_profile_creation_links_account(): void
    {
        $this->signInAs('admin');
        $this->post('/preview/admin/akun', ['action' => 'status', 'id' => 9, 'status' => 'Aktif'])->assertSessionHasErrors('status');

        $this->signInAs('koordinator');
        $this->post('/preview/koordinator/siswa', ['action' => 'save', 'account' => '9', 'nis' => '2026009', 'name' => 'Siswa contoh baru', 'class' => 'XII RPL 1', 'major' => 'RPL', 'email' => 'baru@example.test'])->assertSessionHasNoErrors();

        $this->signInAs('admin');
        $this->post('/preview/admin/akun', ['action' => 'status', 'id' => 9, 'status' => 'Aktif'])->assertSessionHasNoErrors();

        $this->signInAs('koordinator');
        $this->get('/preview/koordinator/siswa?detail=9')->assertOk()->assertSee('Siswa contoh baru');
    }

    public function test_empty_states_filters_and_reset_are_available(): void
    {
        $this->signInAs('siswa');
        $this->get('/preview/siswa/dashboard?state=unplaced')->assertSee('Penempatan belum tersedia');

        $this->signInAs('admin');
        $this->get('/preview/admin/akun?q=tidakada')->assertSee('Tidak ada hasil yang cocok');

        $this->signInAs('pembina');
        $this->get('/preview/pembina/jurnal?q=tidakada')->assertSee('Tidak ada jurnal yang cocok');

        $this->signInAs('admin');
        $this->withSession(['sipkl_preview' => PreviewData::initial()])->post('/preview/reset')->assertRedirect('/')->assertSessionMissing('sipkl_preview');
    }

    public function test_filters_keep_operational_lists_in_the_expected_scope(): void
    {
        $this->signInAs('admin');
        $this->get('/preview/admin/akun?account_role=Admin')->assertSee('nadia.contoh')->assertDontSee('aditya.contoh');

        $this->signInAs('koordinator');
        $this->get('/preview/koordinator/siswa?class=XII%20TKJ%202')->assertSee('Bima Saputra')->assertDontSee('Aditya Pratama');

        $this->signInAs('pembina');
        $this->get('/preview/pembina/absensi?student=Kirana%20Putri')->assertViewHas('table', fn ($table) => count($table['rows']) === 1 && $table['rows'][0]['cells'][1] === 'Kirana Putri');
    }

    public function test_coordinator_dashboard_and_report_use_database_records_not_preview_names(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $placedStudent = Student::factory()->create(['name' => 'Siswa Database Ditempatkan']);
        $unplacedStudent = Student::factory()->create(['name' => 'Siswa Database Belum Ditempatkan']);
        $place = Place::factory()->create(['name' => 'PT Database Aktual']);
        Placement::factory()->create(['student_id' => $placedStudent->id, 'place_id' => $place->id]);

        $this->actingAs($coordinator)
            ->get(route('preview.page', ['role' => 'koordinator', 'page' => 'dashboard']))
            ->assertOk()
            ->assertSee('Siswa Database Ditempatkan')
            ->assertSee('Siswa Database Belum Ditempatkan')
            ->assertSee('PT Database Aktual')
            ->assertDontSee('Bima Saputra')
            ->assertDontSee('Nayla Maharani')
            ->assertDontSee('Aditya Pratama')
            ->assertDontSee('Kirana Putri')
            ->assertDontSee('Studio Nusa Digital');

        $this->get(route('preview.page', ['role' => 'koordinator', 'page' => 'laporan']))
            ->assertOk()
            ->assertSee('Siswa Database Ditempatkan')
            ->assertSee('Siswa Database Belum Ditempatkan')
            ->assertSee('PT Database Aktual')
            ->assertDontSee('Bima Saputra')
            ->assertDontSee('Nayla Maharani')
            ->assertDontSee('Aditya Pratama')
            ->assertDontSee('Kirana Putri')
            ->assertDontSee('Studio Nusa Digital');
    }

    public function test_unsubmitted_journal_drafts_are_not_visible_to_mentor(): void
    {
        $this->signInAs('siswa');
        $this->post('/preview/siswa/jurnal', ['action' => 'draft', 'title' => 'Draf pribadi contoh', 'activity' => 'Belum dikirim', 'date' => '2026-10-08'])->assertSessionHasNoErrors();
        $this->get('/preview/siswa/jurnal')->assertSee('Draf pribadi contoh');

        $this->signInAs('pembina');
        $this->get('/preview/pembina/jurnal')->assertDontSee('Draf pribadi contoh');
        $this->get('/preview/pembina/jurnal?journal=5')->assertNotFound();
    }

    public function test_student_preview_displays_logged_in_student_name_instead_of_default_preview_name(): void
    {
        $studentUser = User::factory()->create([
            'name' => 'Henkun Siswa',
            'email' => 'henkun@sekolah.test',
            'role' => 'siswa',
        ]);

        $this->actingAs($studentUser)
            ->get('/preview/siswa/absensi')
            ->assertOk()
            ->assertSee('Henkun Siswa')
            ->assertDontSee('Aditya Pratama');

        $this->get('/preview/siswa/jurnal')
            ->assertOk()
            ->assertSee('Henkun Siswa')
            ->assertDontSee('Aditya Pratama');

        $this->post('/preview/siswa/absensi', [
            'action' => 'attendance',
            'date' => '2026-10-08',
            'status' => 'Hadir',
            'in' => '08:00',
            'out' => '16:00',
            'note' => 'Hadir tepat waktu',
        ])->assertRedirect('/preview/siswa/absensi');

        $this->get('/preview/siswa/absensi')
            ->assertOk()
            ->assertSee('Henkun Siswa');
    }

    public function test_unlinked_student_can_activate_portal_from_preview(): void
    {
        $studentUser = User::factory()->create([
            'name' => 'Henkun Siswa',
            'email' => 'henkun.aktif@sekolah.test',
            'role' => 'siswa',
        ]);

        $this->assertFalse($studentUser->student()->exists());

        $this->actingAs($studentUser)
            ->post(route('preview.siswa.activate', ['role' => 'siswa']))
            ->assertRedirect(route('student.portal.dashboard'));

        $this->assertTrue($studentUser->fresh()->student()->exists());
        $this->assertTrue($studentUser->fresh()->student->placement()->exists());
    }

    public function test_industry_mentor_gets_industry_menus_and_own_scope(): void
    {
        $user = User::factory()->create(['role' => 'pembina']);
        Mentor::factory()->create(['user_id' => $user->id, 'type' => 'Industri', 'nip' => null, 'position' => 'Supervisor']);
        $this->actingAs($user);

        $this->get('/preview/pembina/dashboard')->assertOk()->assertSee('Pembina Industri')->assertSee('Ringkasan Pembimbingan')->assertSee('Siswa magang');
        foreach (config('preview.roles.pembina.variants.Industri.menus') as $page => $label) {
            $this->get("/preview/pembina/$page")->assertOk()->assertSee($label);
        }
        $this->get('/preview/pembina/siswa')->assertOk()->assertSee('Pembina Sekolah')->assertSee('Aditya Pratama');
        $this->get('/preview/pembina/siswa?detail=3')->assertNotFound();
        $this->get('/preview/pembina/profil')->assertOk()->assertSee('Industri');
        $this->post('/preview/pembina/jurnal', ['action' => 'approve', 'id' => 1, 'note' => ''])->assertSessionHasNoErrors();
        $this->post('/preview/pembina/penilaian', ['action' => 'draft', 'student' => 3, 'grade' => 80])->assertForbidden();
    }
}
