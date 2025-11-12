<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PDSApiController
{
    public $firstSheet;
    public $secondSheet;
    public $thirdSheet;
    public $fourthSheet;
    public $spreadsheet;

    public function index(Request $request, string $employee_no)
    {
        $data = $this->getEmployeeFullData($employee_no);

        $templatePath = public_path('templates/cos/payslip.xlsx');
        $this->spreadsheet  = IOFactory::load($templatePath);

        $sheetNames = $this->spreadsheet->getSheetNames();

        $this->firstSheet = $this->spreadsheet->getSheet(0);
        $this->secondSheet = $this->spreadsheet->getSheet(1);
        $this->thirdSheet = $this->spreadsheet->getSheet(2);
        $this->fourthSheet = $this->spreadsheet->getSheet(3);

        $this->mapFirstSheet($data);

        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $outputPath = public_path('templates/cos/payslip_filled.xlsx');
        $writer->save($outputPath);

        return response()->download($outputPath);
    }

    private function mapFirstSheet($data)
    {
        $fullName = $data['personal']->first_name . ' ' . $data['personal']->last_name;
        $this->firstSheet->setCellValue('C1', $fullName);
    }

    private function getEmployeeFullData($employeeNo)
    {
        $singleRowTables = ['personal', 'family'];
        $allTables = [
            'personal' => 'employee_personal',
            'family' => 'employee_family',
            'children' => 'employee_children',
            'education' => 'employee_education',
            'work_experience' => 'employee_work_experience',
            'civil_service' => 'employee_civil_service',
            'trainings' => 'employee_trainings',
            'voluntary_works' => 'employee_voluntary_works',
            'skills_hobbies' => 'employee_skills_hobbies',
        ];

        $data = [];

        foreach ($allTables as $key => $table) {
            $query = DB::table($table)->where('employee_no', $employeeNo);
            $data[$key] = in_array($key, $singleRowTables) ? $query->first() : $query->get();
        }

        if (!$data['personal']) {
            return ['message' => 'Employee not found'];
        }

        return $data;
    }
}
