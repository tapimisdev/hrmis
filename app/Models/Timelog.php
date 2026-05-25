<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timelog extends Model
{
    use HasFactory;

    protected $table = 'timelogs';

    protected $guarded = [];

    protected $casts = [
        'date_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public const FN_LABELS = [
        0 => 'Checkin',
        1 => 'Checkout',
        2 => 'Breakout',
        3 => 'Break In',
        4 => 'Overtime In',
        5 => 'Overtime Out',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function scopeVerificationListing(Builder $query): Builder
    {
        return $query
            ->leftJoin('employee_information as ei', 'timelogs.user_id', '=', 'ei.user_id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select(
                'timelogs.*',
                'ei.employee_no as employee_information_employee_no',
                'ep.profile',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix'
            )
            ->with([
                'shift:id,name',
                'workSchedule:id,name',
            ]);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('timelogs.employee_no', 'like', "%{$search}%")
                ->orWhere('ei.employee_no', 'like', "%{$search}%")
                ->orWhere('timelogs.user_id', 'like', "%{$search}%")
                ->orWhere('timelogs.biometric_sn', 'like', "%{$search}%")
                ->orWhere('ep.firstname', 'like', "%{$search}%")
                ->orWhere('ep.lastname', 'like', "%{$search}%")
                ->orWhereRaw(
                    "TRIM(CONCAT(COALESCE(ep.firstname, ''), ' ', COALESCE(ep.lastname, ''))) like ?",
                    ["%{$search}%"]
                )
                ->orWhereRaw(
                    "TRIM(CONCAT(COALESCE(ep.lastname, ''), ', ', COALESCE(ep.firstname, ''))) like ?",
                    ["%{$search}%"]
                );
        });
    }

    public function scopeDateRange(Builder $query, ?string $fromDate, ?string $toDate): Builder
    {
        if (!empty($fromDate)) {
            $query->whereDate('timelogs.date_time', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('timelogs.date_time', '<=', $toDate);
        }

        return $query;
    }

    public function getFnLabelAttribute(): string
    {
        return self::FN_LABELS[$this->fn] ?? 'Unknown';
    }

    public function getEmployeeFullnameAttribute(): string
    {
        $parts = array_filter([
            $this->firstname ?? null,
            $this->middlename ?? null,
            $this->lastname ?? null,
            $this->suffix ?? null,
        ], fn ($value) => filled($value));

        return trim(implode(' ', $parts)) ?: 'N/A';
    }

    public function getResolvedEmployeeNoAttribute(): ?string
    {
        return $this->employee_no ?: $this->employee_information_employee_no;
    }
}
