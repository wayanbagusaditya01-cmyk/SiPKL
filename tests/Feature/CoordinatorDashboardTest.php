<?php

namespace Tests\Feature;

use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_coordinator_dashboard(): void
    {
        $this->get(route('coordinator.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_non_coordinator_cannot_access_coordinator_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->get(route('coordinator.dashboard'))
            ->assertForbidden();
    }

    public function test_coordinator_dashboard_renders_with_real_database_data(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $period = PklPeriod::factory()->create([
            'name' => 'Periode PKL 2026/2027',
            'status' => 'Aktif',
        ]);
        $place = Place::factory()->create(['name' => 'PT Teknologi Masa Depan']);
        $placedStudent = Student::factory()->create(['name' => 'Siswa Ditempatkan']);
        $unplacedStudent = Student::factory()->create(['name' => 'Siswa Belum Ditempatkan']);

        Placement::factory()->create([
            'student_id' => $placedStudent->id,
            'place_id' => $place->id,
            'period_id' => $period->id,
            'status' => 'Aktif',
        ]);

        $this->actingAs($coordinator)
            ->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Operasional PKL')
            ->assertSee('PT Teknologi Masa Depan')
            ->assertSee('Siswa Belum Ditempatkan')
            ->assertSee('Periode PKL 2026/2027');
    }

    public function test_coordinator_can_access_all_navigation_modules(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);

        $this->actingAs($coordinator);

        $this->get(route('coordinator.dashboard'))->assertOk();
        $this->get(route('coordinator.siswa.index'))->assertOk();
        $this->get(route('coordinator.pembina.index'))->assertOk();
        $this->get(route('coordinator.tempat.index'))->assertOk();
        $this->get(route('coordinator.periode.index'))->assertOk();
        $this->get(route('coordinator.penempatan.index'))->assertOk();
    }
}
