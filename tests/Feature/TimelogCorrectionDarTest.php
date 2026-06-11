<?php

namespace Tests\Feature;

use App\Enums\FnEnum;
use App\Http\Controllers\Admin\Timekeeping\TimelogCorrectionController;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\EventService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class TimelogCorrectionDarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approved_tcr_moves_existing_dar_to_the_matching_new_timelog(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $shiftId = DB::table('shifts')->insertGetId([
            'name' => 'DAR TCR Test Shift',
            'start_time' => '08:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $workScheduleId = DB::table('work_schedule')->insertGetId([
            'name' => 'DAR TCR Test Schedule',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldTimeInId = DB::table('timelogs')->insertGetId([
            'user_id' => $user->id,
            'employee_no' => 'EMP-001',
            'date_time' => '2026-06-10 08:00:00',
            'shift_id' => $shiftId,
            'work_schedule_id' => $workScheduleId,
            'fn' => FnEnum::TimeIn->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldTimeOutId = DB::table('timelogs')->insertGetId([
            'user_id' => $user->id,
            'employee_no' => 'EMP-001',
            'date_time' => '2026-06-10 17:00:00',
            'shift_id' => $shiftId,
            'work_schedule_id' => $workScheduleId,
            'fn' => FnEnum::TimeOut->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $darPath = 'users/EMP-001/daily-accomplishment-reports/dar-2026-06-10.pdf';
        Storage::disk('public')->put($darPath, 'existing DAR');

        $darId = DB::table('accomplishment_reports')->insertGetId([
            'timelog_id' => $oldTimeOutId,
            'employee_no' => 'EMP-001',
            'file' => $darPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $correctionId = DB::table('timelog_corrections')->insertGetId([
            'reference_no' => 'TCR-20260610-01',
            'employee_no' => 'EMP-001',
            'date' => '2026-06-10',
            'time_in' => '2026-06-10 08:15:00',
            'time_out' => '2026-06-10 17:15:00',
            'shift_id' => $shiftId,
            'work_schedule_id' => $workScheduleId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeService = Mockery::mock(EmployeeService::class);
        $employeeService->shouldReceive('getEmployeeUserId')
            ->once()
            ->with('EMP-001')
            ->andReturn($user->id);
        $this->app->instance(EmployeeService::class, $employeeService);

        $eventService = Mockery::mock(EventService::class);
        $eventService->shouldReceive('pushNotification')->once();

        Auth::shouldReceive('user')->once()->andReturn((object) ['name' => 'HR Admin']);

        $response = (new TimelogCorrectionController($eventService))->approve($correctionId);

        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertDatabaseHas('timelogs', ['id' => $oldTimeInId, 'is_active' => false]);
        $this->assertDatabaseHas('timelogs', ['id' => $oldTimeOutId, 'is_active' => false]);

        $newTimeOutId = DB::table('timelogs')
            ->where('is_active', true)
            ->where('user_id', $user->id)
            ->whereDate('date_time', '2026-06-10')
            ->where('fn', FnEnum::TimeOut->value)
            ->value('id');

        $this->assertNotNull($newTimeOutId);
        $this->assertDatabaseHas('accomplishment_reports', [
            'id' => $darId,
            'timelog_id' => $newTimeOutId,
            'file' => $darPath,
        ]);
        Storage::disk('public')->assertExists($darPath);
    }
}
