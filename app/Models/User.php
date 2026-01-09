<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    protected $appends = ['employment_type_id'];


    protected $employee_no;

    public function employee_no()
    {
        return DB::table('employee_information')
            ->where('user_id', $this->id)
            ->value('employee_no');
    }

    // public function employment_type_id()
    // {
    //     return DB::table('employee_organization')
    //         ->where('employee_no', $this->employee_no())
    //         ->orderByDesc('id')
    //         ->value('employment_type_id');
    // }

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

    public function postedAnnouncements()
    {
        return $this->belongsToMany(EventAnnouncement::class, 'events_announcements_posted_by')
            ->withTimestamps();
    }

    public function viewedAnnouncements()
    {
        return $this->hasMany(EventAnnouncementViewer::class);
    }


    // public function getNameAttribute()
    // {
    //     $employee = DB::table('users as u')
    //         ->leftJoin('employee_information as ei', 'ei.user_id', '=', 'u.id')
    //         ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
    //         ->where('u.id', $this->id)
    //         ->select('ep.firstname', 'ep.lastname')
    //         ->first();

    //     if ($employee) {
    //         return $employee->firstname . ' ' . $employee->lastname;
    //     }

    //     return 'No Name';
    // }


    public function getShiftAndWorkSchedule()
    {
        $employee_no = DB::table('employee_information')->where('user_id', $this->id)->value('employee_no');

        $schedule = DB::table('employee_shift_work_schedule')
            ->where('employee_no', $employee_no)
            ->where('effectivity_date', '<=', now())
            ->first();

        if (!$schedule) {
            throw new \Exception('Please ask your HR to set your Shift and Work Schedule.');
        }

        return [
            'shift_id'         => $schedule->shift_id,
            'work_schedule_id' => $schedule->work_schedule_id,
        ];
    }
}
