<?php

namespace Tests\Feature;

use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceAndPlacementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['preview.enabled' => true]);
    }

    public function test_coordinator_can_create_a_place_in_the_database(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->post(route('coordinator.tempat.store'), [
                'name' => 'PT Tempat Baru',
                'address' => 'Jl. Merdeka 10',
                'contact' => '08123456789',
                'email' => 'kontak@tempat.test',
                'latitude' => '-5.147665',
                'longitude' => '119.432732',
            ])
            ->assertRedirect(route('coordinator.tempat.index'));

        $this->assertDatabaseHas('places', [
            'name' => 'PT Tempat Baru',
            'address' => 'Jl. Merdeka 10',
            'latitude' => '-5.147665',
            'longitude' => '119.432732',
        ]);
    }

    public function test_coordinator_can_update_a_place_in_the_database(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $place = Place::factory()->create(['name' => 'PT Lama']);

        $this->actingAs($coordinator)
            ->put(route('coordinator.tempat.update', $place), [
                'name' => 'PT Diperbarui',
                'address' => 'Jl. Baru 20',
                'contact' => '08123456789',
                'email' => 'baru@tempat.test',
                'latitude' => '-5.140000',
                'longitude' => '119.480000',
            ])
            ->assertRedirect(route('coordinator.tempat.index'));

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'name' => 'PT Diperbarui',
            'latitude' => '-5.140000',
        ]);
    }

    public function test_place_and_placement_pages_show_saved_database_records_only(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create(['name' => 'Siswa Database']);
        $place = Place::factory()->create(['name' => 'PT Tempat Database']);
        Placement::factory()->create(['student_id' => $student->id, 'place_id' => $place->id]);

        $this->actingAs($coordinator)
            ->get(route('coordinator.tempat.index'))
            ->assertOk()
            ->assertSee('PT Tempat Database')
            ->assertDontSee('Studio Nusa Digital');

        $this->get(route('coordinator.penempatan.index'))
            ->assertOk()
            ->assertSee('Siswa Database')
            ->assertSee('PT Tempat Database')
            ->assertDontSee('Studio Nusa Digital');
    }

    public function test_coordinator_can_create_a_student_placement(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create();
        $place = Place::factory()->create();
        $period = PklPeriod::factory()->create();
        $schoolMentor = Mentor::factory()->create(['type' => 'Sekolah']);
        $industryMentor = Mentor::factory()->create(['type' => 'Industri', 'place_id' => $place->id, 'position' => 'Supervisor']);

        $this->actingAs($coordinator)
            ->post(route('coordinator.penempatan.store'), [
                'student_id' => $student->id,
                'place_id' => $place->id,
                'period_id' => $period->id,
                'school_mentor_id' => $schoolMentor->id,
                'industry_mentor_id' => $industryMentor->id,
                'placed_at' => '2026-08-10',
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.penempatan.index'));

        $this->assertDatabaseHas('placements', [
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
            'school_mentor_id' => $schoolMentor->id,
            'industry_mentor_id' => $industryMentor->id,
            'status' => 'Aktif',
        ]);
        $this->assertSame(
            1,
            Placement::where('student_id', $student->id)->whereDate('placed_at', '2026-08-10')->count(),
        );
    }

    public function test_coordinator_can_change_an_existing_student_placement(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create();
        $oldPlace = Place::factory()->create();
        $newPlace = Place::factory()->create();
        $period = PklPeriod::factory()->create();
        $schoolMentor = Mentor::factory()->create(['type' => 'Sekolah']);
        $industryMentor = Mentor::factory()->create(['type' => 'Industri', 'place_id' => $newPlace->id, 'position' => 'Supervisor']);
        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'place_id' => $oldPlace->id,
        ]);

        $this->actingAs($coordinator)
            ->put(route('coordinator.penempatan.update', $placement), [
                'student_id' => $student->id,
                'place_id' => $newPlace->id,
                'period_id' => $period->id,
                'school_mentor_id' => $schoolMentor->id,
                'industry_mentor_id' => $industryMentor->id,
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.penempatan.index'));

        $this->assertDatabaseHas('placements', [
            'id' => $placement->id,
            'student_id' => $student->id,
            'place_id' => $newPlace->id,
        ]);
        $this->assertDatabaseCount('placements', 1);
    }

    public function test_a_student_cannot_be_assigned_to_two_places_at_once(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $student = Student::factory()->create();
        $firstPlace = Place::factory()->create();
        $secondPlace = Place::factory()->create();
        Placement::factory()->create(['student_id' => $student->id, 'place_id' => $firstPlace->id]);

        $this->actingAs($coordinator)
            ->from(route('coordinator.penempatan.create'))
            ->post(route('coordinator.penempatan.store'), [
                'student_id' => $student->id,
                'place_id' => $secondPlace->id,
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('coordinator.penempatan.create'))
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('placements', 1);
    }

    public function test_guest_and_student_cannot_manage_places(): void
    {
        $this->get(route('coordinator.tempat.index'))
            ->assertRedirect(route('login'));

        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->get(route('coordinator.tempat.index'))
            ->assertForbidden();
    }

    public function test_coordinator_navigation_links_to_database_place_and_placement_pages(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->get(route('preview.page', ['role' => 'koordinator', 'page' => 'dashboard']))
            ->assertOk()
            ->assertSee(route('coordinator.tempat.index'), false)
            ->assertSee(route('coordinator.penempatan.index'), false);
    }

    public function test_student_profile_form_no_longer_contains_a_place_input(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator)
            ->get(route('coordinator.siswa.create'))
            ->assertOk()
            ->assertDontSee('name="place_name"', false);

        $this->get(route('coordinator.siswa.create'))
            ->assertSee('Profil siswa dapat disimpan sebelum akun login dibuat.');
    }
}
