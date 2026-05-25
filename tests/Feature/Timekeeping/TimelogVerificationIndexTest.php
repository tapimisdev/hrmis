<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TimelogVerificationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_timelog_verification_view_permission(): void
    {
        Permission::create([
            'name' => 'hr.timelog-verification.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('timekeeping.timelog-verification.index'))
            ->assertForbidden();

        $user->givePermissionTo('hr.timelog-verification.view');

        $this->actingAs($user)
            ->get(route('timekeeping.timelog-verification.index'))
            ->assertOk()
            ->assertSee('Timelog Verification');
    }

    public function test_page_filters_timelogs_by_search_and_date_range(): void
    {
        Permission::create([
            'name' => 'hr.timelog-verification.view',
            'guard_name' => 'web',
        ]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('hr.timelog-verification.view');

        $employeeOne = User::factory()->create(['name' => 'Alice Example']);
        $employeeOneNo = 'EMP-001';

        DB::table('employee_information')->insert([
            'user_id' => $employeeOne->id,
            'employee_no' => $employeeOneNo,
            'account_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_personal')->insert([
            'employee_no' => $employeeOneNo,
            'firstname' => 'Alice',
            'lastname' => 'Example',
            'age' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeTwo = User::factory()->create(['name' => 'Bob Example']);
        $employeeTwoNo = 'EMP-002';

        DB::table('employee_information')->insert([
            'user_id' => $employeeTwo->id,
            'employee_no' => $employeeTwoNo,
            'account_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_personal')->insert([
            'employee_no' => $employeeTwoNo,
            'firstname' => 'Bob',
            'lastname' => 'Example',
            'age' => 31,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shiftId = DB::table('shifts')->insertGetId([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workScheduleId = DB::table('work_schedule')->insertGetId([
            'name' => 'Weekdays',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('timelogs')->insert([
            [
                'user_id' => $employeeOne->id,
                'employee_no' => $employeeOneNo,
                'date_time' => '2026-05-10 08:15:00',
                'shift_id' => $shiftId,
                'work_schedule_id' => $workScheduleId,
                'fn' => 0,
                'biometric_sn' => 'web',
                'is_active' => true,
                'actioned_by' => null,
                'cancelled_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $employeeTwo->id,
                'employee_no' => $employeeTwoNo,
                'date_time' => '2026-04-01 17:00:00',
                'shift_id' => $shiftId,
                'work_schedule_id' => $workScheduleId,
                'fn' => 1,
                'biometric_sn' => 'device-01',
                'is_active' => true,
                'actioned_by' => null,
                'cancelled_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($viewer)
            ->get(route('timekeeping.timelog-verification.index', [
                'search' => 'Alice',
                'from_date' => '2026-05-01',
                'to_date' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee('Alice Example')
            ->assertDontSee('Bob Example')
            ->assertSee('Morning Shift')
            ->assertSee('Weekdays')
            ->assertSee('Checkin');
    }
}
