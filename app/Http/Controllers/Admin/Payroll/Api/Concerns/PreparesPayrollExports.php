<?php

namespace App\Http\Controllers\Admin\Payroll\Api\Concerns;

trait PreparesPayrollExports
{
    private function preparePayrollExport(): void
    {
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);
    }
}
