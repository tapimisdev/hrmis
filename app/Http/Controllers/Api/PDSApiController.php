<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class PDSApiController
{
    /**
     * Export the full PDS of an employee as an Excel file.
     */
    public function index(Request $request, string $employee_no)
    {
        try {

            // Fetch employee data
            $data = $this->getEmployeeFullData($employee_no);

            if (!isset($data['personal'])) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            // Template path
            $templatePath = public_path('templates/cos/pds.xlsx');
            if (!file_exists($templatePath)) {
                return response()->json(['message' => 'Template file not found'], 404);
            }

            // Load the Excel template
            try {
                $spreadsheet = IOFactory::createReader('Xlsx')
                    ->setIncludeCharts(true)
                    ->setReadDataOnly(false)
                    ->load($templatePath);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to load template sheet.',
                    'error'   => $e->getMessage()
                ], 500);
            }

            // Fill sheets (wrap each if needed)
            $this->sheet1($spreadsheet->getSheet(0), $data);
            $this->sheet2($spreadsheet->getSheet(1), $data);
            $this->sheet3($spreadsheet->getSheet(2), $data);

            // Clear any existing output buffer (prevents corrupt Excel output)
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Stream the file
            $response = new StreamedResponse(function () use ($spreadsheet) {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

                try {
                    $writer->save('php://output');
                } catch (\Exception $e) {
                    echo "Error writing XLSX file: " . $e->getMessage();
                }
            });

            $fileName = 'pds_' . strtolower($employee_no) . '.xlsx';

            $response->headers->set(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

            $response->headers->set(
                'Content-Disposition',
                'attachment; filename="' . $fileName . '"'
            );

            $response->headers->set('Cache-Control', 'max-age=0');

            return $response; // IMPORTANT

        } catch (\Throwable $e) {
            // Global catch to handle unexpected errors
            return response()->json([
                'message' => 'Unexpected server error occurred.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fill Sheet 1 (Personal Information, Family, Children, Education)
     */
    private function sheet1($sheet, array $data): void
    {
        $personal  = $data['personal'];
        $family    = $data['family'];
        $children  = $data['children'];
        $education = $data['education'];
        $account = $data['account'];

        $this->fillPersonal($sheet, $personal, $account);
        $this->fillFamily($sheet, $family);
        $this->fillChildren($sheet, $children);
        $this->fillEducation($sheet, $education);
    }

    /**
     * Fill Sheet 2 (Civil Service and Work Experience)
     */
    private function sheet2($sheet, array $data): void
    {
        $civil_service = $data['civil_service'];
        $work_experience = $data['work_experience'];

        $this->fillCSC($sheet, $civil_service);
        $this->fillWorkExp($sheet, $work_experience);
    }

    /**
     * Fill Sheet 3 (Voluntary Works,  Trainings, Skills and Hobbies)
     */
    private function sheet3($sheet, array $data): void
    {
        $voluntary_works = $data['voluntary_works'];
        $trainings = $data['trainings']; 
        $skills_hobbies = $data['skills_hobbies'];

        $this->fillVoluntaryWork($sheet, $voluntary_works);
        $this->fillTrainings($sheet, $trainings);
        $this->fillSkillsHobbies($sheet, $skills_hobbies);
    }

    /* ======================================================
     * SHEET 1 | SECTION 1: PERSONAL DETAILS
     * ====================================================== */
    private function fillPersonal($sheet, $personal, $account): void
    {
        $birthday = $this->formatDate($personal->birthday);

        // Gender checkbox
        $sheet->setCellValue('D16', strtolower($personal->sex) === 'male'
            ? '✔ MALE                 ☐ FEMALE'
            : '☐ MALE       ✔ FEMALE'
        );

        // Civil status checkboxes
        $status = strtolower($personal->civil_status ?? '');
        $single    = $status === 'single'    ? '✔' : '☐';
        $married   = $status === 'married'   ? '✔' : '☐';
        $widowed   = $status === 'widowed'   ? '✔' : '☐';
        $separated = $status === 'separated' ? '✔' : '☐';
        $others    = !in_array($status, ['single', 'married', 'widowed', 'separated']) ? '☑' : '☐';

        // Citizenship
        $citizenship = strtolower($personal->citizenship ?? '');
        $citizenship_type   = strtolower($personal->citizenship_type ?? '');

        $filipino      = $citizenship === 'filipino' ? '✔' : '☐';
        $dualCitizen   = $citizenship === 'dual citizenship' ? '✔' : '☐';

        $byBirth         = ($citizenship === 'dual_citizenship' && $citizenship_type === 'by_birth') ? '✔' : '☐';
        $byNaturalization = ($citizenship === 'dual_citizenship' && $citizenship_type === 'by_naturalization') ? '✔' : '☐';

        foreach (range(17, 21) as $i) $sheet->mergeCells("D{$i}:F{$i}");

        $sheet->setCellValue('D18', "$single SINGLE             $married MARRIED");
        $sheet->setCellValue('D19', "$widowed WIDOWED        $separated SEPARATED");
        $sheet->setCellValue('D20', "$others OTHER/S:");
        
        $sheet->setCellValue('J13', "$filipino FILIPINO             $dualCitizen DUAL CITIZENSHIP");
        $sheet->setCellValue('J14', "$byBirth BY BIRTH             $byNaturalization BY NATURALIZATION");

        $sheet->setCellValue('J16', strtoupper($personal->country ?? 'N/A'));

        // Personal info
        $sheet->setCellValue('D10', strtoupper($personal->lastname ?? ''));
        $sheet->setCellValue('D11', strtoupper($personal->firstname ?? ''));
        $sheet->setCellValue('D12', strtoupper($personal->middlename ?? ''));
        $sheet->setCellValue('D13', $birthday ?? '');
        $sheet->setCellValue('D15', strtoupper($personal->birth_place ?? ''));
        $sheet->setCellValue('D22', strtoupper($personal->height ?? ''));
        $sheet->setCellValue('D24', strtoupper($personal->weight ?? ''));
        $sheet->setCellValue('D25', strtoupper($personal->blood_type ?? ''));
        $sheet->setCellValue('D27', strtoupper($personal->sss_no ?? ''));
        $sheet->setCellValue('D29', strtoupper($personal->pagibig_no ?? ''));
        $sheet->setCellValue('D31', strtoupper($personal->philhealth_no ?? ''));
        $sheet->setCellValue('D32', strtoupper($personal->philsys_no ?? ''));
        $sheet->setCellValue('D33', strtoupper($personal->tin_no ?? ''));
        $sheet->setCellValue('D34', strtoupper($personal->agency_no ?? ''));
        $sheet->setCellValue('N11', strtoupper($personal->suffix ?? ''));

        // Present address
        $sheet->setCellValue('I17', strtoupper($personal->present_block ?? ''));
        $sheet->setCellValue('L17', strtoupper($personal->present_street ?? ''));
        $sheet->setCellValue('I19', strtoupper($personal->present_subdivision ?? ''));
        $sheet->setCellValue('L19', strtoupper($personal->present_barangay ?? ''));
        $sheet->setCellValue('I22', strtoupper($personal->present_city ?? ''));
        $sheet->setCellValue('L22', strtoupper($personal->present_province ?? ''));
        $sheet->setCellValue('I24', strtoupper($personal->present_zip ?? ''));

        // Permanent address
        $sheet->setCellValue('I25', strtoupper($personal->permanent_block ?? ''));
        $sheet->setCellValue('L25', strtoupper($personal->permanent_street ?? ''));
        $sheet->setCellValue('I27', strtoupper($personal->permanent_subdivision ?? ''));
        $sheet->setCellValue('L27', strtoupper($personal->permanent_barangay ?? ''));
        $sheet->setCellValue('I29', strtoupper($personal->permanent_city ?? ''));
        $sheet->setCellValue('L29', strtoupper($personal->permanent_province ?? ''));
        $sheet->setCellValue('I31', strtoupper($personal->permanent_zip ?? ''));

        // Contact
        $sheet->setCellValue('I32', strtoupper($personal->tel_no ?? ''));
        $sheet->setCellValue('I33', strtoupper($personal->mobile_number ?? ''));
        $sheet->setCellValue('I34', strtoupper($account->email ?? ''));
    }

    /* ======================================================
     * SHEET 1 | SECTION 2: FAMILY
     * ====================================================== */
    private function fillFamily($sheet, $family): void
    {
        $sheet->setCellValue('D36', strtoupper($family->spouse_surname ?? ''));
        $sheet->setCellValue('D37', strtoupper($family->spouse_firstname ?? ''));
        $sheet->setCellValue('D38', strtoupper($family->spouse_middlename ?? ''));
        $sheet->setCellValue('D39', strtoupper($family->spouse_occupation ?? ''));
        $sheet->setCellValue('D40', strtoupper($family->spouse_business_name_employer ?? ''));
        $sheet->setCellValue('D41', strtoupper($family->spouse_business_address ?? ''));
        $sheet->setCellValue('D42', strtoupper($family->spouse_contact_no ?? ''));
        $sheet->setCellValue('D43', strtoupper($family->father_surname ?? ''));
        $sheet->setCellValue('D44', strtoupper($family->father_firstname ?? ''));
        $sheet->setCellValue('D45', strtoupper($family->father_middlename ?? ''));
        $sheet->setCellValue('D47', strtoupper($family->mother_surname ?? ''));
        $sheet->setCellValue('D48', strtoupper($family->mother_firstname ?? ''));
        $sheet->setCellValue('D49', strtoupper($family->mother_middlename ?? ''));
    }

    /* ======================================================
     * SHEET 1 | SECTION 3: CHILDREN
     * ====================================================== */
    private function fillChildren($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 37;
        foreach ($data as $item) {
            $name = strtoupper(trim("{$item->firstname} {$item->lastname}"));
            $birthdate = $this->formatDate($item->birthdate);
            $sheet->setCellValue("I{$row}", $name);
            $sheet->setCellValue("M{$row}", $birthdate);
            if (++$row > 48) break;
        }
    }

    /* ======================================================
     * SHEET 1 | SECTION 4: EDUCATION
     * ====================================================== */
    private function fillEducation($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $rows = [
            'elementary' => 54,
            'secondary'  => 55,
            'vocational' => 56,
            'college'    => 57,
            'masters'    => 58,
            'doctoral'   => 59,
        ];

        foreach ($data as $item) {
            $level = strtolower($item->level ?? '');
            if (!isset($rows[$level])) continue;

            $row = $rows[$level];
            $sheet->setCellValue("D{$row}", strtoupper($item->school_name ?? ''));
            $sheet->setCellValue("G{$row}", strtoupper($item->course ?? ''));
            $sheet->setCellValue("J{$row}", $this->formatDate($item->from_year));
            $sheet->setCellValue("K{$row}", $this->formatDate($item->to_year));
            $sheet->setCellValue("L{$row}", strtoupper($item->highest_level ?? ''));
            $sheet->setCellValue("M{$row}", $this->formatDate($item->year_graduated));
            $sheet->setCellValue("N{$row}", strtoupper($item->scholarship_honors ?? ''));
        }
    }

    /* ======================================================
     * SHEET 2 | SECTION 1: CIVIL SERVICE ELIGIBILITY
     * ====================================================== */
    private function fillCSC($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 5;
        
        foreach ($data as $item) {
            $date_exam     = $this->formatDate($item->date_exam);
            $date_validity = $this->formatDate($item->date_validity);

            $sheet->setCellValue("A{$row}", strtoupper($item->certification ?? ''));
            $sheet->setCellValue("F{$row}", strtoupper($item->rating ?? ''));
            $sheet->setCellValue("G{$row}", strtoupper($date_exam));
            $sheet->setCellValue("I{$row}", strtoupper($item->place_exam ?? ''));
            $sheet->setCellValue("J{$row}", strtoupper($item->license_no ?? ''));
            $sheet->setCellValue("K{$row}", strtoupper($date_validity));

            if (++$row > 11) break;
        }

    }

    /* ======================================================
     * SHEET 2 | SECTION 2: WORK EXPERIENCE
     * ====================================================== */
    private function fillWorkExp($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 18;

        foreach ($data as $item) {
            $from_year = $this->formatDate($item->from_year);
            $to_year   = $this->formatDate($item->to_year);

            $sheet->setCellValue("A{$row}", strtoupper($from_year));
            $sheet->setCellValue("C{$row}", strtoupper($to_year));
            $sheet->setCellValue("D{$row}", strtoupper($item->position ?? ''));
            $sheet->setCellValue("G{$row}", strtoupper($item->department ?? ''));
            $sheet->setCellValue("J{$row}", strtoupper($item->employment_status ?? ''));
            $sheet->setCellValue("K{$row}", strtoupper($item->isGovernment ? 'Y' : 'N'));

            if (++$row > 45) break;
        }
    }

    /* ======================================================
     * SHEET 3 | SECTION 1: VOLUNTARY WORKS
     * ====================================================== */
    private function fillVoluntaryWork($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 6;

        foreach ($data as $item) {

            $date_from = Carbon::parse($item->date_from)->format('m/d/Y');
            $date_to = Carbon::parse($item->date_to)->format('m/d/Y');

            $sheet->setCellValue("A{$row}", strtoupper($item->organization));
            $sheet->setCellValue("E{$row}", strtoupper($date_from ?? ''));
            $sheet->setCellValue("F{$row}", strtoupper($date_to ?? ''));
            $sheet->setCellValue("G{$row}", strtoupper($item->consumed_hours ?? ''));
            $sheet->setCellValue("H{$row}", strtoupper($item->position ?? ''));

            if (++$row > 12) break;
        }
    }

    /* ======================================================
     * SHEET 3 | SECTION 2: TRAININGS
     * ====================================================== */
    private function fillTrainings($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 18;

        foreach ($data as $item) {

            $date_from = Carbon::parse($item->date_from)->format('m/d/Y');
            $date_to = Carbon::parse($item->date_to)->format('m/d/Y');

            $sheet->setCellValue("A{$row}", strtoupper($item->name));
            $sheet->setCellValue("E{$row}", strtoupper($date_from ?? ''));
            $sheet->setCellValue("F{$row}", strtoupper($date_to ?? ''));
            $sheet->setCellValue("H{$row}", strtoupper($date->consumed_hours ?? ''));
            $sheet->setCellValue("G{$row}", strtoupper($item->type ?? ''));
            $sheet->setCellValue("I{$row}", strtoupper($item->sponsored_by ?? ''));

            if (++$row > 38) break;
        }
    }

    /* ======================================================
     * SHEET 3 | SECTION 3: SKILLS AND HOBBIES
     * ====================================================== */
    private function fillSkillsHobbies($sheet, $data): void
    {
        if ($data->isEmpty()) return;

        $row = 42;

        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", strtoupper($item->name ?? ''));
            $sheet->setCellValue("C{$row}", strtoupper($item->recognition ?? ''));
            $sheet->setCellValue("I{$row}", strtoupper($item->organization ?? ''));

            if (++$row > 48) break;
        }
    }

    /* ======================================================
     * UTILITIES
     * ====================================================== */
    private function getEmployeeFullData(string $employeeNo): array
    {
        $singleRowTables = ['personal', 'family'];
        $tables = [
            'personal'        => 'employee_personal',
            'family'          => 'employee_family',
            'children'        => 'employee_children',
            'education'       => 'employee_education',
            'work_experience' => 'employee_work_experience',
            'civil_service'   => 'employee_civil_service',
            'trainings'       => 'employee_trainings',
            'voluntary_works' => 'employee_voluntary_works',
            'skills_hobbies'  => 'employee_skills_hobbies',
        ];

        $data = [];

        // ----------------------------------------
        // Fetch normal tables using employee_no
        // ----------------------------------------
        foreach ($tables as $key => $table) {
            $query = DB::table($table)->where('employee_no', $employeeNo);
            $data[$key] = in_array($key, $singleRowTables)
                ? $query->first()
                : $query->get();
        }

        // ----------------------------------------
        // Fetch ACCOUNT from users table:
        // users.id = employee_information.user_id
        // employee_information.employee_no = given employee_no
        // ----------------------------------------
        $data['account'] = DB::table('users')
            ->join('employee_information', 'employee_information.user_id', '=', 'users.id')
            ->where('employee_information.employee_no', $employeeNo)
            ->select('users.*')
            ->first();

        return $data;
    }

    private function formatDate(?string $date): string
    {
        return !empty($date) ? Carbon::parse($date)->format('m/d/Y') : '';
    }
}
