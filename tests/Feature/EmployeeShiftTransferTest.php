<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeShiftTransferTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_appends_shift_history_for_multiple_employees_using_the_processing_date(): void
    {
        Carbon::setTestNow('2026-06-10 09:30:00');

        $employeeNumbers = ['SHIFT-TRANSFER-001', 'SHIFT-TRANSFER-002'];

        foreach ($employeeNumbers as $employeeNumber) {
            DB::table('employee_information')->insert([
                'employee_no' => $employeeNumber,
                'account_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $oldShiftId = DB::table('shifts')->insertGetId([
            'name' => 'Old Shift',
            'start_time' => '08:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $newShiftId = DB::table('shifts')->insertGetId([
            'name' => 'New Shift',
            'start_time' => '09:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $oldScheduleId = DB::table('work_schedule')->insertGetId([
            'name' => 'Old Schedule',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $newScheduleId = DB::table('work_schedule')->insertGetId([
            'name' => 'New Schedule',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_shift_work_schedule')->insert([
            'employee_no' => $employeeNumbers[0],
            'shift_id' => $oldShiftId,
            'work_schedule_id' => $oldScheduleId,
            'effectivity_date' => '2026-01-01',
            'created_at' => '2026-01-01 08:00:00',
            'updated_at' => '2026-01-01 08:00:00',
        ]);

        $response = $this
            ->withoutMiddleware()
            ->postJson(route('hris.employee.transfer-shift'), [
                'employees' => $employeeNumbers,
                'shift_id' => $newShiftId,
                'work_schedule_id' => $newScheduleId,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        foreach ($employeeNumbers as $employeeNumber) {
            $this->assertDatabaseHas('employee_shift_work_schedule', [
                'employee_no' => $employeeNumber,
                'shift_id' => $newShiftId,
                'work_schedule_id' => $newScheduleId,
                'effectivity_date' => '2026-06-10',
            ]);
        }

        $this->assertDatabaseHas('employee_shift_work_schedule', [
            'employee_no' => $employeeNumbers[0],
            'shift_id' => $oldShiftId,
            'work_schedule_id' => $oldScheduleId,
            'effectivity_date' => '2026-01-01',
        ]);

        Carbon::setTestNow();
    }
}
