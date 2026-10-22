<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Mentor;
use App\Models\Notification;
use App\Models\PklGrade;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_and_industry_mentor_logins_open_the_database_mentor_portal(): void
    {
        config(['preview.enabled' => true]);
        $schoolMentor = Mentor::factory()->school()->create();
        $industryMentor = Mentor::factory()->industry()->create();

        foreach ([$schoolMentor, $industryMentor] as $mentor) {
            $mentor->user->update(['password' => 'rahasia']);

            $this->post(route('login.store'), ['email' => $mentor->user->email, 'password' => 'rahasia'])
                ->assertRedirect(route('mentor.dashboard'));

            $this->get(route('mentor.dashboard'))->assertOk()->assertSee($mentor->typeLabel());
            $this->get(route('login'))->assertRedirect(route('mentor.dashboard'));

            $this->post(route('logout'));
        }
    }

    public function test_mentor_portal_rejects_guests_and_other_roles(): void
    {
        $this->get(route('mentor.dashboard'))->assertRedirect(route('login'));

        foreach (['siswa', 'koordinator', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('mentor.students.index'))
                ->assertForbidden();
        }
    }

    public function test_unlinked_mentor_account_sees_guidance_and_cannot_open_data_pages(): void
    {
        $account = User::factory()->create(['role' => 'pembina_industri']);

        $this->actingAs($account)
            ->get(route('mentor.dashboard'))
            ->assertOk()
            ->assertSee('Profil Pembina Belum Terhubung')
            ->assertSee('Pembina Industri');

        $this->get(route('mentor.journals.index'))->assertRedirect(route('mentor.dashboard'));
    }

    public function test_each_mentor_only_sees_students_from_their_own_placement_column(): void
    {
        ['school' => $school, 'industry' => $industry, 'student' => $student] = $this->createGuidedStudent();
        $otherStudent = Placement::factory()->create()->student;

        $this->actingAs($school->user)
            ->get(route('mentor.students.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($industry->user->name)
            ->assertDontSee($otherStudent->name);

        $this->actingAs($industry->user)
            ->get(route('mentor.students.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($school->user->name)
            ->assertDontSee($otherStudent->name);

        $this->get(route('mentor.placements.index'))->assertOk()->assertSee($student->name);
    }

    public function test_mentor_approves_a_waiting_journal_and_the_student_is_notified(): void
    {
        ['industry' => $industry, 'student' => $student] = $this->createGuidedStudent();
        $journal = JournalEntry::factory()->create(['student_id' => $student->id, 'status' => 'Menunggu']);

        $this->actingAs($industry->user)
            ->post(route('mentor.journals.approve', $journal), ['mentor_note' => 'Kerja bagus.'])
            ->assertRedirect(route('mentor.journals.index'));

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
            'status' => 'Disetujui',
            'mentor_note' => 'Kerja bagus.',
            'reviewed_by' => $industry->id,
        ]);
        $this->assertTrue(Notification::where('user_id', $student->user_id)->where('title', 'Jurnal disetujui')->exists());
    }

    public function test_journal_revision_requires_a_note(): void
    {
        ['school' => $school, 'student' => $student] = $this->createGuidedStudent();
        $journal = JournalEntry::factory()->create(['student_id' => $student->id, 'status' => 'Menunggu']);

        $this->actingAs($school->user)
            ->post(route('mentor.journals.revise', $journal), ['mentor_note' => ''])
            ->assertSessionHasErrors('mentor_note');
        $this->assertSame('Menunggu', $journal->fresh()->status);

        $this->post(route('mentor.journals.revise', $journal), ['mentor_note' => 'Lengkapi hasil uji.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Perlu Revisi', $journal->fresh()->status);
    }

    public function test_mentor_cannot_see_drafts_or_review_journals_outside_their_guidance(): void
    {
        ['school' => $school, 'student' => $student] = $this->createGuidedStudent();
        $draft = JournalEntry::factory()->create(['student_id' => $student->id, 'status' => 'Draf', 'title' => 'Draf pribadi siswa']);
        $foreignJournal = JournalEntry::factory()->create([
            'student_id' => Placement::factory()->create()->student_id,
            'status' => 'Menunggu',
        ]);

        $this->actingAs($school->user)
            ->get(route('mentor.journals.index'))
            ->assertOk()
            ->assertDontSee('Draf pribadi siswa');
        $this->get(route('mentor.journals.index', ['journal' => $draft->id]))->assertNotFound();

        $this->post(route('mentor.journals.approve', $foreignJournal))->assertForbidden();
        $this->post(route('mentor.journals.approve', $draft))->assertForbidden();
        $this->assertSame('Menunggu', $foreignJournal->fresh()->status);
    }

    public function test_school_and_industry_mentors_each_keep_their_own_grade_for_a_student(): void
    {
        ['school' => $school, 'industry' => $industry, 'student' => $student] = $this->createGuidedStudent();

        $this->actingAs($school->user)
            ->post(route('mentor.grades.store'), ['student_id' => $student->id, 'score' => '80', 'feedback' => 'Catatan sekolah', 'action' => 'publish'])
            ->assertRedirect(route('mentor.grades.index'));
        $this->actingAs($industry->user)
            ->post(route('mentor.grades.store'), ['student_id' => $student->id, 'score' => '90', 'feedback' => 'Catatan industri', 'action' => 'publish'])
            ->assertRedirect(route('mentor.grades.index'));

        $this->assertDatabaseHas('pkl_grades', ['student_id' => $student->id, 'mentor_id' => $school->id, 'status' => 'Terbit']);
        $this->assertDatabaseHas('pkl_grades', ['student_id' => $student->id, 'mentor_id' => $industry->id, 'status' => 'Terbit']);

        $this->actingAs($student->user)
            ->get(route('student.portal.grade'))
            ->assertOk()
            ->assertSee('Catatan sekolah')
            ->assertSee('Catatan industri')
            ->assertSee('85.00');
    }

    public function test_draft_grade_stays_hidden_from_the_student(): void
    {
        ['school' => $school, 'student' => $student] = $this->createGuidedStudent();

        $this->actingAs($school->user)
            ->post(route('mentor.grades.store'), ['student_id' => $student->id, 'score' => '87.25', 'feedback' => 'Catatan rahasia draf', 'action' => 'draft'])
            ->assertSessionHasNoErrors();

        $this->actingAs($student->user)
            ->get(route('student.portal.grade'))
            ->assertSee('Nilai belum diterbitkan')
            ->assertDontSee('Catatan rahasia draf');
    }

    public function test_grade_rejects_out_of_range_scores_and_students_outside_guidance(): void
    {
        ['school' => $school, 'student' => $student] = $this->createGuidedStudent();
        $otherStudent = Placement::factory()->create()->student;
        $payload = ['student_id' => $student->id, 'score' => '101', 'action' => 'publish'];

        $this->actingAs($school->user)
            ->post(route('mentor.grades.store'), $payload)
            ->assertSessionHasErrors('score');
        $this->post(route('mentor.grades.store'), array_replace($payload, ['score' => '80', 'student_id' => $otherStudent->id]))
            ->assertSessionHasErrors('student_id');
        $this->get(route('mentor.grades.index', ['student' => $otherStudent->id]))->assertForbidden();

        $this->assertSame(0, PklGrade::count());
    }

    public function test_mentor_updates_contact_profile_with_a_unique_email(): void
    {
        $industry = Mentor::factory()->industry()->create();
        $takenEmail = User::factory()->create()->email;

        $this->actingAs($industry->user)
            ->put(route('mentor.profile.update'), ['name' => 'Dimas', 'email' => $takenEmail, 'phone' => '0811'])
            ->assertSessionHasErrors('email');

        $this->put(route('mentor.profile.update'), ['name' => 'Dimas Prakoso', 'email' => 'Dimas@Industri.Test', 'phone' => '08123'])
            ->assertRedirect(route('mentor.profile.edit'));

        $this->assertDatabaseHas('users', ['id' => $industry->user_id, 'name' => 'Dimas Prakoso', 'email' => 'dimas@industri.test']);
        $this->assertSame('08123', $industry->fresh()->phone);
    }

    public function test_coordinator_cannot_link_an_account_to_a_mentor_type_that_contradicts_its_role(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $industryAccount = User::factory()->create(['role' => 'pembina_industri']);

        $this->actingAs($coordinator)
            ->post(route('coordinator.pembina.store'), ['user_id' => $industryAccount->id, 'type' => 'Sekolah', 'nip' => '123'])
            ->assertSessionHasErrors(['type' => 'Akun ini berperan Pembina Industri, sehingga tipe pembina harus Industri.']);

        $this->assertFalse($industryAccount->mentor()->exists());
    }

    public function test_admin_cannot_change_a_linked_mentor_account_to_an_incompatible_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = Mentor::factory()->school()->create();

        $this->actingAs($admin)
            ->put(route('admin.accounts.update', $school->user), [
                'name' => $school->user->name,
                'email' => $school->user->email,
                'role' => 'pembina_industri',
                'status' => '1',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('pembina_sekolah', $school->user->fresh()->role);
    }

    public function test_mentor_preview_pages_redirect_to_the_database_portal(): void
    {
        config(['preview.enabled' => true]);
        $school = Mentor::factory()->school()->create();

        $this->actingAs($school->user)
            ->get(route('preview.page', ['role' => 'pembina_sekolah', 'page' => 'jurnal']))
            ->assertRedirect(route('mentor.journals.index'));
        $this->post(route('preview.action', ['role' => 'pembina_sekolah', 'page' => 'penilaian']), ['action' => 'publish'])
            ->assertForbidden();
    }

    /**
     * @return array{school: Mentor, industry: Mentor, student: Student}
     */
    private function createGuidedStudent(): array
    {
        $school = Mentor::factory()->school()->create();
        $industry = Mentor::factory()->industry()->create();
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'siswa'])->id,
        ]);
        Placement::factory()->create([
            'student_id' => $student->id,
            'place_id' => $industry->place_id,
            'school_mentor_id' => $school->id,
            'industry_mentor_id' => $industry->id,
        ]);

        return ['school' => $school, 'industry' => $industry, 'student' => $student];
    }
}
