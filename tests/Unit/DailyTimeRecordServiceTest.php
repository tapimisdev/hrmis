<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DailyTimeRecordService;

class DailyTimeRecordServiceTest extends TestCase
{
    public function test_getDTR_outputs_real_data(): void
    {
        $this->markTestSkipped('Skipping until proper test data setup.');
        
        // $payload = [
        //     'user_id'   => 76,
        //     'startDate' => '2026-01-01',
        //     'endDate'   => '2026-01-15',
        // ];

        // $service = app(DailyTimeRecordService::class);

        // $result = $service->getDTR($payload);

        // dump($result);

        // $this->assertNotNull($result);
    }
}
