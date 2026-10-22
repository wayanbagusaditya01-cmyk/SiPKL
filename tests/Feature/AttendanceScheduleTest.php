<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\PlaceHoliday;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    private const LATITUDE = -5.147665;

    private const LONGITUDE = 119.432732;

    public function test_industry_mentor_sets_the_schedule_and_location_of_their_place(): void
    {
        $mentor = Mentor::factory()->industry()->create();

        $this->actingAs($mentor->user)
            ->get(route('mentor.schedule.edit'))
            ->assertOk()
            ->assertSee($mentor->place->name);

        $this->put(route('mentor.schedule.update'), [
            'latitude' => self::LATITUDE,
            'longitude' => self::LONGITUDE,
            'attendance_radius' => 200,
            'work_days' => ['1', '2', '3', '4', '5', '6'],
            'check_in_start' => '06:30',
            'check_in_end' => '07:30',
            'check_out_start' => '15:00',
        ])->assertRedirect(route('mentor.schedule.edit'));

        $place = $mentor->place->fresh();
        $this->assertSame([1, 2, 3, 4, 5, 6], $place->workDays());
        $this->assertSame('07:30', $place->scheduleTime('check_in_end'));
        $this->assertSame(200, $place->attendance_radius);
        $this->assertSame('Senin–Sabtu', $place->workDaysLabel());
    }

    public function test_schedule_times_must_be_in_order_and_need_a_work_day(): void
    {
        $mentor = Mentor::factory()->industry()->create();

        $this->actingAs($mentor->user)
            ->put(route('mentor.schedule.update'), [
                'attendance_radius' => 150,
                'work_days' => [],
                'check_in_start' => '08:00',
                'check_in_end' => '07:00',
                'check_out_start' => '06:00',
            ])
            ->assertSessionHasErrors([
                'work_days' => 'Pilih minimal satu hari kerja.',
                'check_in_end' => 'Batas tepat waktu harus setelah jam masuk dibuka.',
                'check_out_start' => 'Jam pulang harus setelah batas tepat waktu.',
            ]);
    }

    public function test_school_mentor_cannot_manage_place_schedules(): void
    {
        $mentor = Mentor::factory()->school()->create();

        $this->actingAs($mentor->user)->get(route('mentor.schedule.edit'))->assertForbidden();
    }

    public function test_industry_mentor_adds_and_removes_only_their_own_holidays(): void
    {
        $mentor = Mentor::factory()->industry()->create();
        $foreignHoliday = PlaceHoliday::factory()->create();

        $this->actingAs($mentor->user)
            ->post(route('mentor.schedule.holidays.store'), ['name' => 'Cuti bersama', 'start_date' => '2026-10-20', 'end_date' => '2026-10-19'])
            ->assertSessionHasErrors('end_date');

        $this->post(route('mentor.schedule.holidays.store'), ['name' => 'Cuti bersama', 'start_date' => '2026-10-19', 'end_date' => '2026-10-20'])
            ->assertRedirect(route('mentor.schedule.edit'));

        $holiday = $mentor->place->holidays()->sole();
        $this->delete(route('mentor.schedule.holidays.destroy', $foreignHoliday))->assertNotFound();
        $this->delete(route('mentor.schedule.holidays.destroy', $holiday))->assertRedirect(route('mentor.schedule.edit'));

        $this->assertModelMissing($holiday);
        $this->assertModelExists($foreignHoliday);
    }

    public function test_check_in_follows_the_opening_time_and_records_lateness(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->travelTo('2026-10-12 06:30:00');
        $this->post(route('student.attendance.check-in'), $this->location())
            ->assertSessionHasErrors(['attendance' => 'Absen masuk dibuka pukul 07:00.']);

        $this->travelTo('2026-10-12 08:20:00');
        $this->post(route('student.attendance.check-in'), $this->location())
            ->assertSessionHas('success', 'Absen masuk tercatat pukul 08:20 (terlambat 20 menit).');

        $this->assertSame(20, Attendance::where('student_id', $student->id)->sole()->late_minutes);
    }

    public function test_early_check_out_is_allowed_and_records_how_early(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->travelTo('2026-10-12 07:30:00');
        $this->post(route('student.attendance.check-in'), $this->location())->assertSessionHasNoErrors();
        $this->get(route('student.attendance.index'))->assertViewHas('state', 'check-out');

        $this->travelTo('2026-10-12 14:45:00');
        $this->postJson(route('student.attendance.check-out'), $this->location())
            ->assertOk()
            ->assertJson([
                'message' => 'Absen pulang tercatat pukul 14:45 (1 jam 15 menit lebih awal).',
                'time' => '14:45',
                'note' => 'Pulang 1 jam 15 menit lebih awal',
            ]);

        $attendance = Attendance::where('student_id', $student->id)->sole();
        $this->assertSame('14:45', $attendance->time_out);
        $this->assertSame(75, $attendance->early_leave_minutes);
    }

    public function test_on_time_check_out_has_no_early_leave_and_check_in_closes_at_check_out_time(): void
    {
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        $this->actingAs($user);

        $this->travelTo('2026-10-12 07:30:00');
        $this->post(route('student.attendance.check-in'), $this->location());
        $this->travelTo('2026-10-12 16:05:00');
        $this->post(route('student.attendance.check-out'), $this->location())
            ->assertSessionHas('success', 'Absen pulang tercatat pukul 16:05.');
        $this->assertNull(Attendance::where('student_id', $student->id)->sole()->early_leave_minutes);

        $this->travelTo('2026-10-13 16:10:00');
        $this->post(route('student.attendance.check-in'), $this->location())
            ->assertSessionHasErrors(['attendance' => 'Waktu absen masuk hari ini sudah lewat.']);
        $this->get(route('student.attendance.index'))->assertViewHas('state', 'missed');
    }

    public function test_no_attendance_on_holidays_or_days_off(): void
    {
        ['user' => $user, 'place' => $place] = $this->createPlacedStudent();
        PlaceHoliday::factory()->create(['place_id' => $place->id, 'start_date' => '2026-10-14', 'end_date' => '2026-10-15', 'name' => 'Libur perusahaan']);
        $this->actingAs($user);

        $this->travelTo('2026-10-15 08:00:00');
        $this->post(route('student.attendance.check-in'), $this->location())
            ->assertSessionHasErrors(['attendance' => 'Hari ini libur: Libur perusahaan.']);
        $this->get(route('student.attendance.index'))->assertViewHas('state', 'holiday')->assertSee('Libur perusahaan');

        $this->travelTo('2026-10-17 08:00:00');
        $this->post(route('student.attendance.check-in'), $this->location())
            ->assertSessionHasErrors(['attendance' => 'Hari ini bukan hari kerja PKL.']);
        $this->post(route('student.attendance.leave'), ['attendance_date' => '2026-10-17', 'note' => 'Sakit.'])
            ->assertSessionHasErrors(['attendance_date' => 'Tanggal ini bukan hari kerja atau sedang libur.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_leave_can_include_an_optional_photo(): void
    {
        Storage::fake('public');
        ['user' => $user, 'student' => $student] = $this->createPlacedStudent();
        $this->actingAs($user);
        $this->travelTo('2026-10-12 09:00:00');

        $this->post(route('student.attendance.leave'), [
            'attendance_date' => '2026-10-09',
            'note' => 'Sakit',
            'leave_photo' => UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('leave_photo');

        $this->post(route('student.attendance.leave'), [
            'attendance_date' => '2026-10-09',
            'note' => 'Sakit, surat dokter terlampir.',
            'leave_photo' => UploadedFile::fake()->image('surat-dokter.jpg'),
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::where('student_id', $student->id)->sole();
        Storage::disk('public')->assertExists($attendance->leave_photo_path);

        $this->get(route('student.attendance.index'))->assertSee('storage/'.$attendance->leave_photo_path);
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: int}
     */
    private function location(): array
    {
        return ['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE, 'accuracy' => 10];
    }

    /**
     * @return array{user: User, student: Student, place: Place}
     */
    private function createPlacedStudent(): array
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $place = Place::factory()->create(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE, 'attendance_radius' => 150]);
        Placement::factory()->create([
            'student_id' => $student->id,
            'place_id' => $place->id,
            'period_id' => PklPeriod::factory()->create()->id,
        ]);

        return ['user' => $user, 'student' => $student, 'place' => $place];
    }
}
