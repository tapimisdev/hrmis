<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles; 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class LeaveCreditsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnWidths,
    WithStyles 
{
    protected string $employee_no;
    protected $leave_id;

    public function __construct(string $employee_no, $leave_id)
    {
        $this->employee_no = $employee_no;
        $this->leave_id = $leave_id;
    }

    public function collection()
    {
        return DB::table('leave_credits')
            ->where('employee_no', $this->employee_no)
            ->where('leave_id', $this->leave_id)
            ->orderBy('as_of', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'EMPLOYEE NO.',
            'LEAVE ID',
            'PREVIOUS',
            'EARNED',
            'DEDUCTED',
            'BALANCE',
            'AS OF',
            'REMARKS',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_no,
            $row->leave_id,
            $row->previous,
            $row->earned,
            $row->deducted,
            $row->balance,
            Carbon::parse($row->as_of)->format('F Y'),
            $row->remarks,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 38,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [ 
                'font' => ['bold' => true],
            ],
        ];
    }
}
