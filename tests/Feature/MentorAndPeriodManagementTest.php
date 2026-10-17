<?php

namespace Tests\Feature;

use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorAndPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_coordinator_can_create_a_school_mentor_profile_for_a_mentor_account(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $mentorAccount = User::factory()->create(['role' => 'pembina', 'status' => true]);

        $this->actingAs($coordinator)
            ->post(route('coordinator.pembina.store'), [
                'user_id' => $mentorAccount->id,
                'type' => 'Sekolah',
                'nip' => '19880001',
                'phone' => '08123456789',
            ])
            ->assertRedirect(route('coordinator.pembina.index'));

        $this->assertDatabaseHas('mentors', [
            'user_id' => $mentorAccount->id,
            'type' => 'Sekolah',
            'nip' => '19880001',
        ]);
    }

    public function test_coordinator_can_create_an_industry_mentor_bound_to_a_place(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $mentorAccount = User::factory()->create(['role' => 'pembina', 'status' => true]);
        $place = Place::factory()->create();

        $this->actingAs($coordinator)
            ->post(route('coordinator.pembina.store'), [
                'user_id' => $mentorAccount->id,
                'type' => 'Industri',
                'position' => 'Supervisor',
                'phone' => '08123456789',
                'place_id' => $place->id,
            ])
            ->assertRedirect(route('coordinator.pembina.index'));

        $this->assertDatabaseHas('mentors', [
            'user_id' => $mentorAccount->id,
            'type' => 'Industri',
            'place_id' => $place->id,
            'position' => 'Supervisor',
        ]);
    }

    public function test_coordinator_cannot_link_a_non_mentor_account_to_a_mentor_profile(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $studentAccount = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($coordinator)
            ->from(route('coordinator.pembina.create'))
            ->post(route('coordinator.pembina.store'), [
                'user_id' => $studentAccount->id,
                'type' => 'Sekolah',
            ])
            ->assertRedirect(route('coordinator.pembina.create'))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('mentors', ['user_id' => $studentAccount->id]);
    }

    public function test_coordinator_can_create_and_update_a_pkl_period(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->post(route('coordinator.periode.store'), [
                'name' => 'PKL Tahun 2027',
                'academic_year' => '2027/2028',
                'start_date' => '2027-08-01',
                'end_date' => '2027-10-31',
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.periode.index'));

        $period = PklPeriod::where('name', 'PKL Tahun 2027')->firstOrFail();

        $this->assertDatabaseHas('pkl_periods', [
            'id' => $period->id,
            'academic_year' => '2027/2028',
            'status' => 'Aktif',
        ]);

        $this->put(route('coordinator.periode.update', $period), [
            'name' => 'PKL Tahun Ajaran 2027',
            'academic_year' => '2027/2028',
            'start_date' => '2027-08-01',
            'end_date' => '2027-11-15',
            'status' => 'Aktif',
        ])->assertRedirect(route('coordinator.periode.index'));

        $this->assertDatabaseHas('pkl_periods', ['id' => $period->id]);
        $this->assertSame(1, PklPeriod::whereKey($period->id)->whereDate('end_date', '2027-11-15')->count());
    }

    public function test_period_rejects_an_end_date_before_its_start_date(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->from(route('coordinator.periode.create'))
            ->post(route('coordinator.periode.store'), [
                'name' => 'Periode Tidak Valid',
                'academic_year' => '2027/2028',
                'start_date' => '2027-10-01',
                'end_date' => '2027-09-01',
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.periode.create'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('pkl_periods', ['name' => 'Periode Tidak Valid']);
    }

    public function test_placement_requires_period_and_mentors_for_the_selected_place(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create();
        $place = Place::factory()->create();
        $otherPlace = Place::factory()->create();
        $period = PklPeriod::factory()->create();
        $schoolMentor = Mentor::factory()->create(['type' => 'Sekolah', 'place_id' => null]);
        $industryMentor = Mentor::factory()->create(['type' => 'Industri', 'place_id' => $otherPlace->id]);

        $this->actingAs($coordinator)
            ->from(route('coordinator.penempatan.create'))
            ->post(route('coordinator.penempatan.store'), [
                'student_id' => $student->id,
                'place_id' => $place->id,
                'period_id' => $period->id,
                'school_mentor_id' => $schoolMentor->id,
                'industry_mentor_id' => $industryMentor->id,
                'placed_at' => '2026-08-15',
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.penempatan.create'))
            ->assertSessionHasErrors('industry_mentor_id');

        $this->assertDatabaseMissing('placements', ['student_id' => $student->id]);

        $industryMentor->update(['place_id' => $place->id]);

        $this->post(route('coordinator.penempatan.store'), [
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
            'school_mentor_id' => $schoolMentor->id,
            'industry_mentor_id' => $industryMentor->id,
            'placed_at' => '2026-08-15',
            'status' => 'Aktif',
        ])->assertRedirect(route('coordinator.penempatan.index'));

        $this->assertDatabaseHas('placements', [
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
            'school_mentor_id' => $schoolMentor->id,
            'industry_mentor_id' => $industryMentor->id,
        ]);
    }

    public function test_coordinator_dashboard_links_to_database_mentor_and_period_menus(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->get(route('preview.page', ['role' => 'koordinator', 'page' => 'dashboard']))
            ->assertOk()
            ->assertSee(route('coordinator.pembina.index'), false)
            ->assertSee(route('coordinator.periode.index'), false);
    }

    public function test_guest_and_non_coordinator_cannot_manage_mentor_profiles(): void
    {
        $this->get(route('coordinator.pembina.index'))
            ->assertRedirect(route('login'));

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('coordinator.pembina.index'))
            ->assertForbidden();
    }
}
