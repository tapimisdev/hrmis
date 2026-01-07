<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        if(request()->ajax()){
            $user = request()->user();

            $employee_personal = DB::table('employee_personal as ep')
                ->leftJoin('employee_information as ei', 'ep.employee_no', '=', 'ei.employee_no')
                ->where('ei.user_id', $user->id)
                ->select([
                    'ep.employee_no',
                    'ep.profile',
                    'ep.firstname',
                    'ep.middlename',
                    'ep.lastname',
                    'ep.suffix',
                    'ep.birthday',
                    'ep.age',
                    'ep.civil_status',
                    'ep.sex',
                    'ep.citizenship',
                    'ep.citizenship_type',
                    'ep.country',
                    'ep.birth_place',
                    'ep.birth_certificate',
                    'ep.marriage_certificate',
                    'ep.present_block',
                    'ep.present_street',
                    'ep.present_subdivision',
                    'ep.present_barangay',
                    'ep.present_city',
                    'ep.present_province',
                    'ep.present_zip',
                    'ep.permanent_block',
                    'ep.permanent_street',
                    'ep.permanent_subdivision',
                    'ep.permanent_barangay',
                    'ep.permanent_city',
                    'ep.permanent_province',
                    'ep.permanent_zip',
                    'ep.mobile_number',
                    'ep.tel_no',
                    'ep.height',
                    'ep.weight',
                    'ep.blood_type',
                    'ep.gsis_no',
                    'ep.pagibig_no',
                    'ep.philhealth_no',
                    'ep.sss_no',
                    'ep.tin_no',
                    'ep.philsys_no',
                    'ep.created_at',
                    'ep.updated_at',
                    'ei.date_hired_organization',
                    'ei.date_hired_company',
                    'ei.biometrics_id',
                ])
                ->first();

            if ($employee_personal->profile) {
                $employee_personal->profile = Storage::url('uploads/employees/' . $employee_personal->employee_no . '/profile/' . $employee_personal->profile);
            } else {
                $employee_personal->profile = 'https://ui-avatars.com/api/?name='
                    . urlencode(($employee_personal->firstname ?? '?') . ' ' . ($employee_personal->lastname ?? '?'))
                    . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
            }

            $employee_organization = DB::table('employee_organization as eo')
                ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
                ->leftJoin('units as u', 'eo.unit_id', '=', 'u.id')
                ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
                ->leftJoin('employment_types as et', 'eo.employment_type_id', '=', 'et.id')
                ->where('eo.employee_no', $employee_personal->employee_no ?? null)
                ->select([
                    'eo.effectivity_date',
                    'p.name as position_name',
                    'p.code as position_code',

                    'u.name as unit_name',
                    'u.code as unit_code',

                    'd.name as division_name',
                    'd.code as division_code',

                    'et.name as employment_type_name',
                    'et.code as employment_type_code',
                ])
                ->first();


            return response()->json([
                'user' => $user,
                'personal' => $employee_personal,
                'organization' => $employee_organization,
            ]);

        }

        return view('employee.pages.profile.index');
    }
}
