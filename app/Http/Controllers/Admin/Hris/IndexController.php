<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class IndexController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
    }


    public function index(Request $request)
    {

        if ($request->ajax()) {

            $status = $request->get('account_status');
            $division_id = $request->get('division');
            $unit_id = $request->get('unit');

            $query = $this->employeeService->getEmployees($status, $division_id, $unit_id);

            return $this->datatable($query);
        }

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        return view('admin.pages.hris.index', compact(
            'divisions', 'division_id', 'unit_id'
        ));
    }

    public function remove(string $employee_no)
    {
        DB::beginTransaction();
        
        try {

            $this->employeeService->delete($employee_no);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'employee #' . $employee_no . ' has been removed',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'error occured: ' . $e->getMessage()
            ]);

        }
    }

    public function restore(string $employee_no)
    {
        DB::beginTransaction();
        
        try {

            $this->employeeService->restore($employee_no);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'employee #' . $employee_no . ' has been restored',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'error occured: ' . $e->getMessage()
            ]);

        }
    }


    private function setStatus(string $status) {
        switch($status) {
            case 'active':
                return '<div class="alert alert-sm text-center mb-0 px-1 py-2 alert-success">active</div>';
            case 'inactive':
                return '<div class="alert alert-sm text-center mb-0 px-1 py-2 alert-secondary">inactive</div>';
            case 'archived':
                return '<div class="alert alert-sm text-center mb-0 px-1 py-2 alert-danger">Archived</div>';
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->editColumn('profile', function ($row) {
                $profile = $row->profile ?? null;

                if ($profile) {
                    $profile = Storage::url('uploads/employees/' . $row->employee_no . '/profile/' . $row->profile);
                } else {
                    $profile = 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '') . ' ' . ($row->lastname ?? ''))
                        . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                }

                return '<div style="width: 50px; height: 50px; border:1px solid #ccc; border-radius:8px; 
                                    display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                            <img src="' . $profile . '" 
                                alt="Avatar of ' . e(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')) . '" 
                                style="width:100%; height:100%; object-fit:cover;">
                        </div>';
            })
            ->editColumn('employee_no', function ($row) {
                return $row->employee_no;
            })
            ->editColumn('name', function ($row) {
                return empty($row->firstname) && empty($row->lastname)
                    ? '<i class="text-muted">No Name Yet</i>'
                    : $row->firstname . ' ' . $row->lastname;
            })
            ->editColumn('date_hired', function ($row) {
                return $row->date_hired ? Carbon::parse($row->date_hired)->format('F d, Y')
                    : '';
            })
            ->addColumn('actions', function ($row) {
                $div = '<div class="d-block d-md-flex gap-2 justify-content-start">';

                if ($row->account_status != 'archived') {
                    $div .= '
                        <a href="' . route('hris.employee.transfer', [
                            'employee_no' => $row->employee_no
                        ]) . '" 
                            class="btn btn-outline-primary btn ms-1 my-1" 
                            title="Transfer Unit">
                               <i class="fa-solid fa-retweet"></i>
                        </a>
                        <a href="' . route('hris.employee.salary', [
                            'employee_no' => $row->employee_no
                        ]) . '" 
                            class="btn btn-outline-primary btn ms-1 my-1" 
                            title="Update Salary">
                               <i class="fa-solid fa-money-bills"></i>
                        </a>
                        <a href="' . route('hris.employee.information', [
                            'employee_no' => $row->employee_no
                        ]) . '" 
                            class="btn btn-outline-secondary btn ms-1 my-1" 
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button id="btn-delete"
                            class="btn btn-outline-danger btn ms-1 my-1" 
                            data-target="' . route('hris.employee.remove', [
                                'employee_no' => $row->employee_no
                            ]) . '"
                            title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                        </button>
                    ';
                }

                if ($row->account_status == 'archived') {
                    $div .= '
                        <button id="btn-restore"
                            class="btn btn-outline-success btn ms-1 my-1"
                            data-target="' . route('hris.employee.restore', [
                                'employee_no' => $row->employee_no
                            ]) . '"
                            title="Restore">
                                <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    ';
                }

                $div .= '</div>';

                return $div;
            })


            ->rawColumns(['profile','name', 'account_status', 'actions'])
            ->make(true);
    }
}
