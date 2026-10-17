<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Notification;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentOptionalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_profile_page(): void
    {
        $studentData = $this->createLinkedStudent();

        $this->actingAs($studentData['user'])
            ->get(route('student.profile.edit'))
            ->assertOk()
            ->assertSee($studentData['student']->nis)
            ->assertSee($studentData['student']->name)
            ->assertSee($studentData['student']->class)
            ->assertSee($studentData['student']->major)
            ->assertSee($studentData['user']->email);
    }

    public function test_student_can_update_profile_contact_data(): void
    {
        $studentData = $this->createLinkedStudent();

        $this->actingAs($studentData['user'])
            ->put(route('student.profile.update'), [
                'name' => 'Nama Siswa Baru',
                'email' => 'siswabaru@sekolah.sch.id',
            ])
            ->assertRedirect(route('student.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'id' => $studentData['student']->id,
            'name' => 'Nama Siswa Baru',
            'email' => 'siswabaru@sekolah.sch.id',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $studentData['user']->id,
            'name' => 'Nama Siswa Baru',
        ]);
    }

    public function test_student_can_update_password_with_correct_current_password(): void
    {
        $studentData = $this->createLinkedStudent();

        $this->actingAs($studentData['user'])
            ->put(route('student.profile.password'), [
                'current_password' => 'rahasia',
                'password' => 'passwordbaru123',
                'password_confirmation' => 'passwordbaru123',
            ])
            ->assertRedirect(route('student.profile.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('passwordbaru123', $studentData['user']->fresh()->password));
    }

    public function test_student_cannot_update_password_with_wrong_current_password(): void
    {
        $studentData = $this->createLinkedStudent();

        $this->actingAs($studentData['user'])
            ->from(route('student.profile.edit'))
            ->put(route('student.profile.password'), [
                'current_password' => 'passwordsalah',
                'password' => 'passwordbaru123',
                'password_confirmation' => 'passwordbaru123',
            ])
            ->assertRedirect(route('student.profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('rahasia', $studentData['user']->fresh()->password));
    }

    public function test_student_can_delete_draft_journal(): void
    {
        $studentData = $this->createLinkedStudent();
        $journal = JournalEntry::factory()->create([
            'student_id' => $studentData['student']->id,
            'status' => 'Draf',
        ]);

        $this->actingAs($studentData['user'])
            ->delete(route('student.journals.destroy', $journal))
            ->assertRedirect(route('student.journals.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('journal_entries', [
            'id' => $journal->id,
        ]);
    }

    public function test_student_cannot_delete_submitted_journal(): void
    {
        $studentData = $this->createLinkedStudent();
        $journal = JournalEntry::factory()->create([
            'student_id' => $studentData['student']->id,
            'status' => 'Menunggu',
        ]);

        $this->actingAs($studentData['user'])
            ->delete(route('student.journals.destroy', $journal))
            ->assertForbidden();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
            'status' => 'Menunggu',
        ]);
    }

    public function test_student_cannot_delete_another_students_draft_journal(): void
    {
        $studentData = $this->createLinkedStudent();
        $otherStudentData = $this->createLinkedStudent();
        $journal = JournalEntry::factory()->create([
            'student_id' => $otherStudentData['student']->id,
            'status' => 'Draf',
        ]);

        $this->actingAs($studentData['user'])
            ->delete(route('student.journals.destroy', $journal))
            ->assertNotFound();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
        ]);
    }

    public function test_student_can_create_journal_with_file_upload(): void
    {
        Storage::fake('public');
        $studentData = $this->createLinkedStudent();
        $file = UploadedFile::fake()->image('dokumentasi.jpg');

        $this->actingAs($studentData['user'])
            ->post(route('student.journals.store'), [
                'activity_date' => '2026-10-13',
                'title' => 'Membuat Dokumentasi Foto',
                'activity' => 'Melakukan pekerjaan teknis dan mengunggah foto.',
                'documentation_file' => $file,
                'action' => 'draft',
            ])
            ->assertRedirect(route('student.journals.index'));

        $journal = JournalEntry::where('student_id', $studentData['student']->id)->firstOrFail();
        $this->assertNotNull($journal->documentation_path);
        Storage::disk('public')->assertExists($journal->documentation_path);
    }

    public function test_student_can_update_journal_and_replace_file(): void
    {
        Storage::fake('public');
        $studentData = $this->createLinkedStudent();
        $oldFile = UploadedFile::fake()->image('lama.jpg');
        $oldPath = $oldFile->store('journal_documentations', 'public');

        $journal = JournalEntry::factory()->create([
            'student_id' => $studentData['student']->id,
            'status' => 'Draf',
            'documentation_path' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->image('baru.png');

        $this->actingAs($studentData['user'])
            ->put(route('student.journals.update', $journal), [
                'activity_date' => '2026-10-13',
                'title' => 'Judul Diperbarui',
                'activity' => 'Uraian aktivitas baru dengan foto baru.',
                'documentation_file' => $newFile,
                'action' => 'draft',
            ])
            ->assertRedirect(route('student.journals.index'));

        $journal->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($journal->documentation_path);
    }

    public function test_deleting_draft_journal_removes_uploaded_file(): void
    {
        Storage::fake('public');
        $studentData = $this->createLinkedStudent();
        $file = UploadedFile::fake()->image('foto_draf.jpg');
        $path = $file->store('journal_documentations', 'public');

        $journal = JournalEntry::factory()->create([
            'student_id' => $studentData['student']->id,
            'status' => 'Draf',
            'documentation_path' => $path,
        ]);

        $this->actingAs($studentData['user'])
            ->delete(route('student.journals.destroy', $journal))
            ->assertRedirect(route('student.journals.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);
    }

    public function test_student_can_view_notifications_list(): void
    {
        $studentData = $this->createLinkedStudent();
        $notif1 = Notification::factory()->create([
            'user_id' => $studentData['user']->id,
            'title' => 'Jurnal Disetujui',
            'message' => 'Pembina telah menyetujui jurnal tanggal 12-10-2026.',
            'read_at' => null,
        ]);
        $notif2 = Notification::factory()->read()->create([
            'user_id' => $studentData['user']->id,
            'title' => 'Pengumuman Lama',
            'message' => 'Notifikasi yang sudah dibaca.',
        ]);

        $this->actingAs($studentData['user'])
            ->get(route('student.notifications.index'))
            ->assertOk()
            ->assertSee('Jurnal Disetujui')
            ->assertSee('Pengumuman Lama');

        $this->get(route('student.notifications.index', ['unread' => 1]))
            ->assertOk()
            ->assertSee('Jurnal Disetujui')
            ->assertDontSee('Pengumuman Lama');
    }

    public function test_student_can_mark_notification_as_read(): void
    {
        $studentData = $this->createLinkedStudent();
        $notif = Notification::factory()->create([
            'user_id' => $studentData['user']->id,
            'link' => route('student.portal.grade'),
            'read_at' => null,
        ]);

        $this->actingAs($studentData['user'])
            ->patch(route('student.notifications.read', $notif))
            ->assertRedirect(route('student.portal.grade'));

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $studentData = $this->createLinkedStudent();
        Notification::factory()->count(3)->create([
            'user_id' => $studentData['user']->id,
            'read_at' => null,
        ]);

        $this->assertSame(3, $studentData['user']->unreadNotifications()->count());

        $this->actingAs($studentData['user'])
            ->post(route('student.notifications.readAll'))
            ->assertRedirect(route('student.notifications.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, $studentData['user']->unreadNotifications()->count());
    }

    public function test_student_cannot_read_another_students_notification(): void
    {
        $studentData = $this->createLinkedStudent();
        $otherStudent = $this->createLinkedStudent();
        $notif = Notification::factory()->create([
            'user_id' => $otherStudent['user']->id,
            'read_at' => null,
        ]);

        $this->actingAs($studentData['user'])
            ->patch(route('student.notifications.read', $notif))
            ->assertNotFound();

        $this->assertNull($notif->fresh()->read_at);
    }

    public function test_multiple_students_have_distinct_names_and_isolated_records(): void
    {
        $period = PklPeriod::factory()->create();
        $place = Place::factory()->create();

        // Siswa 1: Hendra
        $userHendra = User::factory()->create([
            'name' => 'Hendra',
            'email' => 'hendra@sipkl.ti',
            'role' => 'siswa',
        ]);
        $studentHendra = Student::factory()->create([
            'user_id' => $userHendra->id,
            'name' => 'Hendra',
            'nis' => '6769',
        ]);
        Placement::factory()->create([
            'student_id' => $studentHendra->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
        ]);

        // Siswa 2: Bagus
        $userBagus = User::factory()->create([
            'name' => 'Bagus',
            'email' => 'bagus@sipkl.ti',
            'role' => 'siswa',
        ]);
        $studentBagus = Student::factory()->create([
            'user_id' => $userBagus->id,
            'name' => 'Bagus',
            'nis' => '6767',
        ]);
        Placement::factory()->create([
            'student_id' => $studentBagus->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
        ]);

        // Hendra login: periksa nama & kirim absensi + jurnal
        $this->actingAs($userHendra);
        $this->get(route('student.portal.dashboard'))
            ->assertOk()
            ->assertSee('Hendra')
            ->assertDontSee('Bagus')
            ->assertDontSee('Aditya Pratama');

        $this->post(route('student.attendance.store'), [
            'attendance_date' => '2026-10-12',
            'status' => 'Hadir',
            'time_in' => '08:00',
            'time_out' => '16:00',
        ])->assertRedirect(route('student.attendance.index'));

        $this->post(route('student.journals.store'), [
            'activity_date' => '2026-10-12',
            'title' => 'Kegiatan Hendra Hari Ini',
            'activity' => 'Melakukan testing fitur backend.',
            'action' => 'draft',
        ])->assertRedirect(route('student.journals.index'));

        // Bagus login: harus melihat namanya sendiri dan TIDAK melihat jurnal/absensi Hendra
        $this->actingAs($userBagus);
        $this->get(route('student.portal.dashboard'))
            ->assertOk()
            ->assertSee('Bagus')
            ->assertDontSee('Hendra')
            ->assertDontSee('Kegiatan Hendra Hari Ini');

        $this->get(route('student.attendance.index'))
            ->assertOk()
            ->assertDontSee('12-10-2026');

        $this->get(route('student.journals.index'))
            ->assertOk()
            ->assertDontSee('Kegiatan Hendra Hari Ini');
    }

    /**
     * @return array{user: User, student: Student, place: Place, period: PklPeriod, placement: Placement}
     */
    private function createLinkedStudent(): array
    {
        $studentAccount = User::factory()->create([
            'role' => 'siswa',
            'password' => 'rahasia',
        ]);
        $student = Student::factory()->create(['user_id' => $studentAccount->id]);
        $place = Place::factory()->create();
        $period = PklPeriod::factory()->create();
        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
        ]);

        return [
            'user' => $studentAccount,
            'student' => $student,
            'place' => $place,
            'period' => $period,
            'placement' => $placement,
        ];
    }
}
