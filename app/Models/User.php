<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Services\SalaryEmloyeeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['employment_type_id', 'is_division_chief'];


    protected $employee_no;

    public function employee_no()
    {
        return DB::table('employee_information')
            ->where('user_id', $this->id)
            ->value('employee_no');
    }
    
    public function getEmployeeNoAttribute() {
        return $this->employeeInformation->employee_no ?? null;
    }

    public function employment_type_id()
    {
        return DB::table('employee_organization')
            ->where('employee_no', $this->employee_no())
            ->orderByDesc('id')
            ->value('employment_type_id');
    }

    public function getEmploymentTypeIdAttribute()
    {
        return DB::table('employee_organization')
            ->where('employee_no', $this->employee_no())
            ->orderByDesc('id')
            ->value('employment_type_id');
    }

    public function employeeInformation()
    {
        return $this->hasOne(EmployeeInformation::class, 'user_id', 'id');
    }

    public function getTodayTimeIn()
    {
        $employeeNo = $this->employeeInformation?->employee_no;

        if (!$employeeNo) {
            return null; 
        }

        return DB::table('timelogs')
            ->where('employee_no', $employeeNo)
            ->whereDate('date_time', now()->toDateString())
            ->where('fn', 0)
            ->orderBy('date_time', 'asc') 
            ->value('date_time');
    }

    public function postedAnnouncements()
    {
        return $this->belongsToMany(EventAnnouncement::class, 'events_announcements_posted_by')
            ->withTimestamps();
    }

    public function viewedAnnouncements()
    {
        return $this->hasMany(EventAnnouncementViewer::class);
    }

    public function getShiftAndWorkSchedule()
    {
        $salaryEmployeeService = new SalaryEmloyeeService();

        $employee_no = DB::table('employee_information')->where('user_id', $this->id)->value('employee_no');

        $schedule = $salaryEmployeeService->activeShift($employee_no)->first();

        if (!$schedule) {
            throw new \Exception('Please ask your HR to set your Shift and Work Schedule.');
        }

        return [
            'shift_id'         => $schedule->shift_id,
            'work_schedule_id' => $schedule->work_schedule_id,
        ];
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function managedDivisions(): HasMany
    {
        return $this->hasMany(Division::class, 'division_manager_id', 'id');
    }

    public function getIsDivisionChiefAttribute(): bool
    {
        return $this->managedDivisions()->exists();
    }
}
