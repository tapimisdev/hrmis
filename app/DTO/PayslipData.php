<?php
// app/DTO/PayslipData.php
namespace App\DTO;

class PayslipData
{
    public function __construct(
        public string $employee_no,
        public int $month,
        public int $year,
        public string $employee_type // 'cos' or 'regular'
    ) {}
}
