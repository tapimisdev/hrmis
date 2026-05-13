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
        $this->middleware('permission:hr.hris.view')->only('index');
        $this->middleware('permission:hr.hris.delete')->only(['remove', 'restore']);
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {

            $status = $request->get('account_status');
            $division_id = $request->get('division');
            $unit_id = $request->get('unit');
            $employment_type = $request->get('employment_type');

            $query = $this->employeeService->getEmployees($status, $division_id, $unit_id, $employment_type);

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

    public function datatable($query)
    {
        $filterParams = request()->only([
            'account_status',
            'division',
            'unit',
            'employment_type',
        ]);

        return DataTables::of($query)
            ->editColumn('profile', function ($row) {
                $profile = $row->profile ?? null;

                if ($profile) {
                    $profile = Storage::url('public/users/' . $row->employee_no . '/profile-image/' . $row->profile);
                } else {
                    $profile = 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
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
            ->editColumn('biometrics_id', function ($row) {
                return '#'.$row->biometrics_id;
            })
            ->editColumn('name', function ($row) {
                return empty($row->firstname) && empty($row->lastname)
                    ? '<i class="text-muted">No Name Yet</i>'
                    : $row->firstname . ' ' . $row->lastname;
            })
            ->editColumn('date_hired', function ($row) {
                return $row->date_hired_organization ? Carbon::parse($row->date_hired_organization)->format('F d, Y')
                    : '';
            })
            ->addColumn('actions', function ($row) use ($filterParams) {
                $div = '<div class="d-block d-flex gap-2 justify-content-start">';

                if ($row->account_status != 'archived') {
                    $div .= '
                        <a href="' . route('daily-time-record.index', ['employee_no' => $row->employee_no]) . '" 
                        class="btn btn-warning me-1" 
                        title="Timelogs">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </a>
                        <a href="' . route('hris.employee.transfer', array_merge(['employee_no' => $row->employee_no], $filterParams)) . '" 
                        class="btn btn-secondary me-1" 
                        title="Transfer Unit">
                            <i class="fa-solid fa-retweet"></i>
                        </a>
                        <a href="' . route('hris.employee.information', array_merge(['employee_no' => $row->employee_no], $filterParams)) . '" 
                        class="btn btn-primary me-1" 
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-dark me-1 download-pds" 
                            data-url="' . route('reports.employee.pds', ['employee_no' => $row->employee_no]) . '" 
                            title="Download PDS">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <button id="btn-delete" 
                            class="btn btn-danger" 
                            data-target="' . route('hris.employee.remove', ['employee_no' => $row->employee_no]) . '" 
                            title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    ';
                }

                if ($row->account_status == 'archived') {
                    $div .= '
                        <button id="btn-restore" 
                            class="btn btn-success ms-1" 
                            data-target="' . route('hris.employee.restore', ['employee_no' => $row->employee_no]) . '" 
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
