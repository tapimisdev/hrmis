<?php

namespace App\Console\Commands;

use App\Services\EmployeeService;
use Illuminate\Console\Command;

class UpdateEmployeeNo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:employee_no
                            {old_employee_no : Current employee number}
                            {new_employee_no : New employee number to apply}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update an employee number across all tables that store employee_no';

    /**
     * Execute the console command.
     */
    public function handle(EmployeeService $employeeService): int
    {
        $oldEmployeeNo = trim((string) $this->argument('old_employee_no'));
        $newEmployeeNo = trim((string) $this->argument('new_employee_no'));

        if ($oldEmployeeNo === '' || $newEmployeeNo === '') {
            $this->error('Both old and new employee numbers are required.');

            return self::FAILURE;
        }

        if ($oldEmployeeNo === $newEmployeeNo) {
            $this->error('Old and new employee numbers must be different.');

            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                "Update employee number from [{$oldEmployeeNo}] to [{$newEmployeeNo}] across all mapped tables?",
                false
            );

            if (!$confirmed) {
                $this->warn('Command cancelled.');

                return self::INVALID;
            }
        }

        try {
            $updatedTables = $employeeService->syncEmployeeNo($oldEmployeeNo, $newEmployeeNo);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($updatedTables === []) {
            $this->warn('No rows were updated.');

            return self::SUCCESS;
        }

        $this->info("Employee number updated from {$oldEmployeeNo} to {$newEmployeeNo}.");

        $rows = [];
        $total = 0;

        foreach ($updatedTables as $table => $count) {
            $rows[] = [
                'table' => $table,
                'rows_updated' => $count,
            ];
            $total += $count;
        }

        $this->table(['Table', 'Rows Updated'], $rows);
        $this->line("Total rows updated: {$total}");

        return self::SUCCESS;
    }
}
