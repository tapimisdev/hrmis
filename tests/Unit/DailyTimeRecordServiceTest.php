<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\DailyTimeRecordService;

class DailyTimeRecordServiceTest extends TestCase
{
    public function test_getDTR_outputs_real_data(): void
    {
        $payload = [
            'user_id'   => 76,
            'startDate' => '2026-01-01',
            'endDate'   => '2026-01-15',
        ];

        // Resolve service from Laravel container (boots facades properly)
        $service = app(DailyTimeRecordService::class);

        $result = $service->getDTR($payload);

        dump($result);

        $this->assertNotNull($result);
    }
}
