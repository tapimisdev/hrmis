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

    protected $employee_no;

    public function getEmployeeNo()
    {
        $this->employee_no = DB::table('employee_information')->where('user_id', $this->id)->value('employee_no');
    }

    public function employeeInformation()
    {
        return $this->hasOne(EmployeeInformation::class, 'user_id', 'id');
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

}
