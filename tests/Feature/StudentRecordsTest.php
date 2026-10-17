<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\JournalEntry;
use App\Models\PklGrade;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_student_login_opens_the_database_student_dashboard(): void
    {
        config(['preview.enabled' => true]);
        $studentData = $this->createLinkedStudent();

        $this->post(route('login.store'), [
            'email' => $studentData['user']->email,
            'password' => 'rahasia',
        ])->assertRedirect(route('student.portal.dashboard'));

        $this->assertAuthenticatedAs($studentData['user']);
    }

    public function test_student_attendance_is_persisted_and_cannot_duplicate_a_day(): void
    {
        $studentData = $this->createLinkedStudent();
        $this->actingAs($studentData['user']);

        $this->post(route('student.attendance.store'), [
            'attendance_date' => '2026-10-12',
            'status' => 'Hadir',
            'time_in' => '08:00',
            'time_out' => '16:00',
        ])->assertRedirect(route('student.attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentData['student']->id,
            'status' => 'Hadir',
        ]);
        $this->assertSame(
            1,
            Attendance::where('student_id', $studentData['student']->id)->whereDate('attendance_date', '2026-10-12')->count(),
        );

        $this->from(route('student.attendance.index'))
            ->post(route('student.attendance.store'), [
                'attendance_date' => '2026-10-12',
                'status' => 'Hadir',
                'time_in' => '08:05',
                'time_out' => '16:00',
            ])->assertRedirect(route('student.attendance.index'))
            ->assertSessionHasErrors('attendance_date');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_student_cannot_save_attendance_without_an_active_placement(): void
    {
        $studentAccount = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $studentAccount->id]);

        $this->actingAs($studentAccount)
            ->from(route('student.attendance.index'))
            ->post(route('student.attendance.store'), [
                'attendance_date' => '2026-10-12',
                'status' => 'Hadir',
                'time_in' => '08:00',
                'time_out' => '16:00',
            ])->assertRedirect(route('student.attendance.index'))
            ->assertSessionHasErrors('attendance_date');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_student_can_save_a_journal_draft_then_submit_it(): void
    {
        $studentData = $this->createLinkedStudent();
        $this->actingAs($studentData['user']);
        $payload = [
            'activity_date' => '2026-10-13',
            'title' => 'Menguji laporan',
            'activity' => 'Menguji hasil dan mencatat pembelajaran.',
            'documentation_url' => 'https://example.test/laporan',
        ];

        $this->post(route('student.journals.store'), $payload + ['action' => 'draft'])
            ->assertRedirect(route('student.journals.index'));

        $journal = JournalEntry::where('student_id', $studentData['student']->id)->firstOrFail();
        $this->assertSame('Draf', $journal->status);

        $this->put(route('student.journals.update', $journal), $payload + ['action' => 'send'])
            ->assertRedirect(route('student.journals.index'));

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
            'student_id' => $studentData['student']->id,
            'status' => 'Menunggu',
        ]);
    }

    public function test_student_cannot_edit_another_students_journal(): void
    {
        $currentStudentData = $this->createLinkedStudent();
        $otherStudentData = $this->createLinkedStudent();
        $journal = JournalEntry::factory()->create(['student_id' => $otherStudentData['student']->id]);

        $this->actingAs($currentStudentData['user'])
            ->put(route('student.journals.update', $journal), [
                'activity_date' => '2026-10-13',
                'title' => 'Tidak boleh diubah',
                'activity' => 'Percobaan akses silang.',
                'action' => 'send',
            ])->assertNotFound();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
            'student_id' => $otherStudentData['student']->id,
            'title' => $journal->title,
        ]);
    }

    public function test_student_grade_page_only_shows_a_published_database_grade(): void
    {
        $studentData = $this->createLinkedStudent();
        $grade = PklGrade::factory()->create([
            'student_id' => $studentData['student']->id,
            'status' => 'Draf',
        ]);

        $this->actingAs($studentData['user'])
            ->get(route('student.portal.grade'))
            ->assertOk()
            ->assertSee('Nilai belum diterbitkan')
            ->assertDontSee((string) $grade->score);

        $grade->update(['status' => 'Terbit']);

        $this->get(route('student.portal.grade'))
            ->assertOk()
            ->assertSee(number_format((float) $grade->score, 2))
            ->assertSee($grade->feedback);
    }

    public function test_student_dashboard_and_activity_lists_use_owned_database_records(): void
    {
        $studentData = $this->createLinkedStudent();
        Attendance::factory()->create(['student_id' => $studentData['student']->id]);
        JournalEntry::factory()->create([
            'student_id' => $studentData['student']->id,
            'title' => 'Jurnal database siswa',
            'status' => 'Menunggu',
        ]);

        $this->actingAs($studentData['user'])
            ->get(route('student.portal.dashboard'))
            ->assertOk()
            ->assertSee('Jurnal database siswa')
            ->assertDontSee('Studio Nusa Digital')
            ->assertDontSee('Menguji formulir absensi');

        $this->get(route('student.journals.index'))
            ->assertOk()
            ->assertSee('Jurnal database siswa')
            ->assertDontSee('Menguji formulir absensi');

        $this->get(route('student.attendance.index'))
            ->assertOk()
            ->assertSee('12-10-2026');
    }

    public function test_guest_and_non_student_cannot_access_student_database_pages(): void
    {
        $this->get(route('student.portal.dashboard'))
            ->assertRedirect(route('login'));

        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->get(route('student.portal.dashboard'))
            ->assertForbidden();
    }

    public function test_linked_student_preview_pages_redirect_to_the_database_portal(): void
    {
        $studentData = $this->createLinkedStudent();
        $routes = [
            'dashboard' => route('student.portal.dashboard'),
            'penempatan' => route('student.portal.placement'),
            'tempat-pkl' => route('student.portal.place'),
            'pembina' => route('student.portal.mentors'),
            'absensi' => route('student.attendance.index'),
            'jurnal' => route('student.journals.index'),
            'nilai' => route('student.portal.grade'),
        ];

        $this->actingAs($studentData['user']);

        foreach ($routes as $page => $target) {
            $this->get(route('preview.page', ['role' => 'siswa', 'page' => $page]))
                ->assertRedirect($target);
        }
    }

    public function test_linked_student_cannot_write_records_through_preview_actions(): void
    {
        $studentData = $this->createLinkedStudent();

        $this->actingAs($studentData['user'])
            ->post(route('preview.action', ['role' => 'siswa', 'page' => 'absensi']), [
                'action' => 'attendance',
                'date' => '2026-10-12',
                'status' => 'Hadir',
                'in' => '08:00',
                'out' => '16:00',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
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
