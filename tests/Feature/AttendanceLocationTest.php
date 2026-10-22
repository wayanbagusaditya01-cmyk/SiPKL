<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceLocationTest extends TestCase
{
    use RefreshDatabase;

    private const PLACE_LATITUDE = -5.147665;

    private const PLACE_LONGITUDE = 119.432732;

    /**
     * Sekitar 50 m ke utara dari titik tempat PKL.
     */
    private const NEARBY_LATITUDE = -5.147215;

    /**
     * Sekitar 500 m ke utara dari titik tempat PKL.
     */
    private const FAR_LATITUDE = -5.143165;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-12 07:45:00');
    }

    public function test_check_in_inside_the_radius_records_server_time_and_location(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();

        $this->actingAs($user)
            ->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE, accuracy: 12.4))
            ->assertRedirect(route('student.attendance.index'))
            ->assertSessionHas('success', 'Absen masuk tercatat pukul 07:45.');

        $attendance = Attendance::where('student_id', $student->id)->sole();
        $this->assertSame('2026-10-12', $attendance->attendance_date->toDateString());
        $this->assertSame('Hadir', $attendance->status);
        $this->assertSame('07:45', $attendance->time_in);
        $this->assertNull($attendance->time_out);
        $this->assertSame(13, (int) $attendance->check_in_accuracy);
        $this->assertEqualsWithDelta(50, $attendance->check_in_distance, 2);
    }

    public function test_check_in_outside_the_radius_is_rejected(): void
    {
        ['user' => $user] = $this->createPlacedStudent();

        $this->actingAs($user)
            ->from(route('student.attendance.index'))
            ->post(route('student.attendance.check-in'), $this->location(self::FAR_LATITUDE))
            ->assertRedirect(route('student.attendance.index'))
            ->assertSessionHasErrors('location');

        $this->assertStringContainsString('radius 150 m', session('errors')->first('location'));
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_with_imprecise_gps_is_rejected(): void
    {
        ['user' => $user] = $this->createPlacedStudent();

        $this->actingAs($user)
            ->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE, accuracy: 350))
            ->assertSessionHasErrors(['location' => 'Akurasi lokasi ±350 m terlalu rendah (maksimal ±100 m). Aktifkan GPS lalu coba lagi di area terbuka.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_requires_a_location_and_place_coordinates(): void
    {
        ['user' => $user, 'place' => $place] = $this->createPlacedStudent();

        $this->actingAs($user)
            ->post(route('student.attendance.check-in'), [])
            ->assertSessionHasErrors(['latitude', 'longitude', 'accuracy']);

        $place->update(['latitude' => null, 'longitude' => null]);

        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertSessionHasErrors(['location' => 'Titik lokasi tempat PKL belum diatur. Hubungi Pembina Industri.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_is_rejected_outside_the_period_and_twice_on_the_same_day(): void
    {
        ['user' => $user] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))->assertSessionHasNoErrors();
        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertSessionHasErrors(['attendance' => 'Absensi hari ini sudah tercatat.']);

        $this->travelTo('2026-11-03 08:00:00');
        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertSessionHasErrors(['attendance' => 'Hari ini berada di luar periode PKL.']);

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_check_out_needs_a_check_in_and_records_the_leaving_location_once(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->post(route('student.attendance.check-out'), $this->location(self::NEARBY_LATITUDE))
            ->assertSessionHasErrors(['attendance' => 'Lakukan absen masuk terlebih dahulu.']);

        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE));
        $this->travelTo('2026-10-12 16:30:00');

        $this->post(route('student.attendance.check-out'), $this->location(self::FAR_LATITUDE))
            ->assertSessionHasErrors('location');
        $this->post(route('student.attendance.check-out'), $this->location(self::PLACE_LATITUDE))
            ->assertSessionHasNoErrors();
        $this->post(route('student.attendance.check-out'), $this->location(self::PLACE_LATITUDE))
            ->assertSessionHasErrors(['attendance' => 'Absensi hari ini sudah selesai.']);

        $attendance = Attendance::where('student_id', $student->id)->sole();
        $this->assertSame('16:30', substr($attendance->time_out, 0, 5));
        $this->assertSame(0, $attendance->check_out_distance);
    }

    public function test_check_in_answers_json_for_the_interactive_page(): void
    {
        ['user' => $user] = $this->createPlacedStudent();

        $this->actingAs($user)
            ->postJson(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertOk()
            ->assertJson(['message' => 'Absen masuk tercatat pukul 07:45.']);

        $this->postJson(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attendance' => 'Absensi hari ini sudah tercatat.']);
    }

    public function test_leave_is_limited_to_today_or_earlier_within_the_period(): void
    {
        ['user' => $user] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-10-13', 'note' => 'Sakit.'])
            ->assertSessionHasErrors(['attendance_date' => 'Izin hanya dapat diajukan untuk hari ini atau tanggal sebelumnya.']);
        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-07-30', 'note' => 'Sakit.'])
            ->assertSessionHasErrors(['attendance_date' => 'Tanggal izin harus berada di dalam periode PKL.']);
        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-10-09', 'note' => ''])
            ->assertSessionHasErrors('note');

        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-10-09', 'note' => 'Sakit, ada surat dokter.'])
            ->assertSessionHasNoErrors();
        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-10-12', 'note' => 'Urusan keluarga.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Sakit, ada surat dokter.', Attendance::whereDate('attendance_date', '2026-10-09')->where('status', 'Izin')->sole()->note);
        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE))
            ->assertSessionHasErrors(['attendance' => 'Absensi hari ini sudah tercatat.']);
    }

    public function test_attendance_page_shows_the_action_for_today_and_location_history(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => '2026-10-09',
            'check_in_distance' => 37,
        ]);

        $this->actingAs($user)
            ->get(route('student.attendance.index'))
            ->assertOk()
            ->assertViewHas('state', 'check-in')
            ->assertSee('Mencari lokasi')
            ->assertSee('08:00–16:00')
            ->assertViewHas('summary', fn (array $summary) => $summary['present'] === 1 && $summary['rate'] === 2);

        $this->post(route('student.attendance.check-in'), $this->location(self::NEARBY_LATITUDE));

        $this->get(route('student.attendance.index'))
            ->assertViewHas('state', 'check-out-wait')
            ->assertSee('Pulang 16:00')
            ->assertSee('07:45');
    }

    public function test_mentor_sees_the_check_in_distance_and_map_link(): void
    {
        ['student' => $student, 'placement' => $placement] = $this->createPlacedStudent();
        $mentor = Mentor::factory()->school()->create();
        $placement->update(['school_mentor_id' => $mentor->id]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'check_in_latitude' => self::NEARBY_LATITUDE,
            'check_in_longitude' => self::PLACE_LONGITUDE,
            'check_in_distance' => 50,
        ]);

        $this->actingAs($mentor->user)
            ->get(route('mentor.attendance.index'))
            ->assertOk()
            ->assertSee('Masuk: 50 m')
            ->assertSee('https://www.google.com/maps?q=-5.1472150,119.4327320', false);
    }

    public function test_coordinator_sets_the_attendance_radius_within_limits(): void
    {
        $coordinator = User::factory()->create(['role' => 'koordinator']);
        $place = Place::factory()->create();
        $payload = ['name' => $place->name, 'latitude' => self::PLACE_LATITUDE, 'longitude' => self::PLACE_LONGITUDE];

        $this->actingAs($coordinator)
            ->put(route('coordinator.tempat.update', $place), [...$payload, 'attendance_radius' => 5000])
            ->assertSessionHasErrors('attendance_radius');

        $this->put(route('coordinator.tempat.update', $place), [...$payload, 'attendance_radius' => 300])
            ->assertSessionHasNoErrors();

        $this->assertSame(300, $place->fresh()->attendance_radius);
    }

    public function test_journal_documentation_accepts_photos_only(): void
    {
        Storage::fake('public');
        ['user' => $user] = $this->createPlacedStudent();
        $payload = ['activity_date' => '2026-10-12', 'title' => 'Dokumentasi', 'activity' => 'Kegiatan hari ini.', 'action' => 'draft'];

        $this->actingAs($user)
            ->post(route('student.journals.store'), [...$payload, 'documentation_file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('documentation_file');

        $this->post(route('student.journals.store'), [...$payload, 'documentation_file' => UploadedFile::fake()->image('foto.webp')])
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: float}
     */
    private function location(float $latitude, float $accuracy = 15): array
    {
        return ['latitude' => $latitude, 'longitude' => self::PLACE_LONGITUDE, 'accuracy' => $accuracy];
    }

    /**
     * @return array{user: User, student: Student, place: Place, placement: Placement}
     */
    private function createPlacedStudent(): array
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $place = Place::factory()->create([
            'latitude' => self::PLACE_LATITUDE,
            'longitude' => self::PLACE_LONGITUDE,
            'attendance_radius' => 150,
        ]);
        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => PklPeriod::factory()->create()->id,
        ]);

        return ['user' => $user, 'student' => $student, 'place' => $place, 'placement' => $placement];
    }
}
