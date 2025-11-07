<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hris\StoreEmployeeImportRequest;
use App\Http\Requests\Admin\Hris\UploadEmployeeRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use function PHPUnit\Framework\isEmpty;

class ImportEmployeeController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:hr.hris.view')->only('index');
        $this->middleware('permission:hr.hris.import_employee')->only(['upload', 'store']);
    }
    public function index()
    {
        return view('admin.pages.hris.import.index');
    }

    public function upload(UploadEmployeeRequest $request)
    {
        $validatedData = $request->validated();

        // Read Excel into array (first sheet)
        $rows = Excel::toArray([], $validatedData['file'])[0];
        $header = $rows[1];
        $dataRows = array_slice($rows, 2);
        
        // Combine header with each row
        $final = [];
        foreach ($dataRows as $row) {
            $final[] = array_combine($header, $row);
        }

        $mapped['employees'] = collect($dataRows)->map(function ($row) use ($header, $validatedData) {
            $assoc = array_combine($header, $row);

           $salary_cutoff = $assoc['salary_frequency'] === 'twice' 
                ? null 
                : ($assoc['salary_cutoff'] ?? null);

            $tranche      = $assoc['tranche'] ?? null;
            $step         = $assoc['step'] ?? null;
            $total_salary = $assoc['total_salary'] ?? null; 
            
            if ($tranche) {
                $tranche = DB::table('tranche')
                    ->where('date', $tranche)
                    ->where('employment_type_id', $validatedData['employment_type'])
                    ->value('id');

                if ($tranche) {
                    $db_tranche_item = DB::table('tranche_items')
                        ->where('tranche_id', $tranche)
                        ->where('salary_grade', $assoc['salary_grade'])
                        ->first();

                    if ($db_tranche_item) {

                        if ($validatedData['employment_type'] === EmploymentTypesEnum::REGULAR->value) {
                            $stepColumn   = 'step_' . $step;
                            $total_salary = $db_tranche_item->{$stepColumn} ?? null;
                            $total_salary = $total_salary ? (float) str_replace(',', '', $total_salary) : null;
                            $daily_rate = round($total_salary / 22, 2);
                        }

                        if ($validatedData['employment_type'] === EmploymentTypesEnum::COS->value) {    
                            $total_salary = $db_tranche_item->step_1 ?? null;
                            $total_salary = $total_salary ? (float) str_replace(',', '', $total_salary) : null;
                            $daily_rate = round($total_salary / 22, 2);
                        }

                    } else {
                        // reset if not found
                        $tranche      = null;
                        $step         = null;
                        $total_salary = null;
                    }
                }
            }


            $data = [
                "employee_no"        => $assoc['employee_no'] ?? null,
                "firstname"          => $assoc['firstname'] ?? null,
                "middlename"         => $assoc['middlename'] ?? null,
                "lastname"           => $assoc['lastname'] ?? null,
                "suffix"             => $assoc['suffix'] ?? null,
                "email"              => $assoc['email'] ?? null,
                "bio_id"             => $assoc['bio_id'] ?? null,
                "date_hired"         => $assoc['date_hired'] ?? null,
                "isActive"           => strtolower($assoc['isActive'] ?? 'inactive'), // normalize
                "position"           => $assoc['position'] ?? null,
                "tranche"            => $tranche ?? null,
                "step"               => $step ?? null,
                "salary_grade"       => $assoc['salary_grade'] ?? null,
                "salary_frequency"   => $assoc['salary_frequency'] ?? null,
                "total_salary"       => $total_salary ?? null,
                "daily_rate"         => $daily_rate ?? null,
                "salary_cutoff"      => $salary_cutoff,
                "deduction_on"       => $assoc['deduction_on'] ?? null,
                "salary_method"      => $assoc['salary_method'] ?? null,
                "payroll_account_no" => $assoc['payroll_account_no'] ?? null,
            ];
            

            return $data;
        });

        $mapped['details'] = [
            'employment_type_id' => $validatedData['employment_type'],
            'division_id'        => $validatedData['division'],
            'unit_id'            => $validatedData['unit'],
            'shift_id'           => $validatedData['shift'],
            'work_schedule_id'   => $validatedData['schedule'],
        ];

        return response()->json([
            'success' => true,
            'data'    => $mapped
        ]);
    }

    public function store(StoreEmployeeImportRequest $request)
    {
        $validatedData = $request->validated();
        $default_password = 'iamdostemployee';
        DB::beginTransaction();
        try {

            $employees = $validatedData['employees'];

            foreach ($employees as $emp) {

                $name = $emp['firstname'] . ' ' . $emp['middlename'] . ' ' . $emp['lastname'];

                $user = User::create([
                    'name' => $name,
                    'email' => $emp['email'],
                    'password' => Hash::make($default_password)
                ]);

                if($validatedData['details']['employment_type_id'] === EmploymentTypesEnum::COS->value) {
                    $user->assignRole('emp_contractual');
                } else if ($validatedData['details']['employment_type_id'] === EmploymentTypesEnum::REGULAR->value) {
                    $user->assignRole('emp_regular');
                } else {
                    throw new \Exception('Invalid employment type. Cannot assign role.');
                }

                DB::table('employee_information')->insert([
                    'employee_no'       => $emp['employee_no'],
                    'biometrics_id'     => $emp['bio_id'],
                    'account_status'    => $emp['isActive'],
                    'date_hired'        => $emp['date_hired'],
                    'salary_method'     => $emp['salary_method'],
                    'payroll_account_no'=> $emp['payroll_account_no'],
                    'user_id'           => $user->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);


                DB::table('employee_personal')->insert([
                    'employee_no'   => $emp['employee_no'],
                    'firstname'     => $emp['firstname'],
                    'middlename'    => $emp['middlename'],
                    'lastname'      => $emp['lastname'],
                    'suffix'        => $emp['suffix'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                DB::table('employee_salary')->insert([
                    'employee_no'       => $emp['employee_no'],
                    'tranche_id'        => $emp['tranche'],
                    'step'              => $emp['step'],
                    'salary_grade'      => $emp['salary_grade'],
                    'salary_frequency'  => $emp['salary_frequency'],
                    'salary_basis'      => 'monthly',
                    'amount'            => $emp['total_salary'],
                    'daily_rate'        => $emp['daily_rate'],
                    'deduction_applied' => $emp['deduction_on'],
                    'effectivity_date'  => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $exploded = explode(' ', $emp['position']);
                $lastWord = array_pop($exploded);

                $abbr = '';
                foreach ($exploded as $word) {
                    $abbr .= strtoupper($word[0]);
                }

                $position_code = $abbr . ' ' . $lastWord;

                // Insert or update the position
                DB::table('positions')->updateOrInsert(
                    ['name' => $emp['position']],
                    [
                        'updated_at'            => now(), 
                        'code'                  => $position_code, 
                        'employment_type_id'    => $validatedData['details']['employment_type_id'],
                    ]
                );

                // Get the position id (since updateOrInsert doesn’t return it)
                $position = DB::table('positions')->where('name', $emp['position'])->first();

                DB::table('employee_organization')->insert([
                    'employee_no'        => $emp['employee_no'],
                    'division_id'        => $validatedData['details']['division_id'],
                    'unit_id'            => $validatedData['details']['unit_id'],
                    'employment_type_id' => $validatedData['details']['employment_type_id'],
                    'position_id'        => $position->id,
                    'effectivity_date'   => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                DB::table('employee_shift_work_schedule')->insert([
                    'employee_no'       => $emp['employee_no'],
                    'shift_id'          => $validatedData['details']['shift_id'],
                    'work_schedule_id'  => $validatedData['details']['work_schedule_id'],
                    'effectivity_date'  => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::commit();

            return response([
                'message' => 'Employees are succesfully imported', 
                'status' => 'succes'], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response([
                'message' => $e->getMessage(), 
                'status' => 'store failed'], 500);
        }
    }
}
