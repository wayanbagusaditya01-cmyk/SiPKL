<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'name', 'address', 'contact', 'email', 'latitude', 'longitude', 'attendance_radius',
    'work_days', 'check_in_start', 'check_in_end', 'check_out_start',
])]
class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory;

    public const DAY_NAMES = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /**
     * Jadwal bawaan bila Pembina Industri belum mengatur jadwal tempat PKL.
     */
    private const DEFAULT_SCHEDULE = [
        'work_days' => [1, 2, 3, 4, 5],
        'check_in_start' => '07:00',
        'check_in_end' => '08:00',
        'check_out_start' => '16:00',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(PlaceHoliday::class)->orderBy('start_date');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Hari kerja dalam format ISO (1 = Senin ... 7 = Minggu).
     *
     * @return array<int, int>
     */
    public function workDays(): array
    {
        return $this->work_days ?: self::DEFAULT_SCHEDULE['work_days'];
    }

    public function workDaysLabel(): string
    {
        $days = $this->workDays();
        sort($days);
        $isConsecutive = count($days) > 2 && end($days) - $days[0] === count($days) - 1;

        return $isConsecutive
            ? self::DAY_NAMES[$days[0]].'–'.self::DAY_NAMES[end($days)]
            : collect($days)->map(fn (int $day): string => self::DAY_NAMES[$day])->implode(', ');
    }

    /**
     * Jam jadwal (check_in_start, check_in_end, atau check_out_start) dalam format H:i.
     */
    public function scheduleTime(string $column): string
    {
        return substr($this->{$column} ?? self::DEFAULT_SCHEDULE[$column], 0, 5);
    }

    /**
     * Jam jadwal pada tanggal tertentu.
     */
    public function scheduleAt(CarbonInterface $date, string $column): Carbon
    {
        return Carbon::parse($date->format('Y-m-d').' '.$this->scheduleTime($column));
    }

    public function holidayOn(CarbonInterface $date): ?PlaceHoliday
    {
        return $this->holidays->first(
            fn (PlaceHoliday $holiday): bool => $date->copy()->startOfDay()->betweenIncluded($holiday->start_date, $holiday->end_date),
        );
    }

    public function isWorkDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->workDays(), true) && $this->holidayOn($date) === null;
    }

    public function workDaysBetween(CarbonInterface $from, CarbonInterface $until): int
    {
        if ($from->gt($until)) {
            return 0;
        }

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()))
            ->filter(fn (CarbonInterface $date): bool => $this->isWorkDay($date))
            ->count();
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'attendance_radius' => 'integer',
            'work_days' => 'array',
        ];
    }
}
