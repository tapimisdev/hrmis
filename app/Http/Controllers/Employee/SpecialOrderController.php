<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Admin\Services\ApplicationController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreSpecialOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SpecialOrderController extends Controller
{
    public function __construct(protected ApplicationController $applicationService)
    {
        $this->middleware('permission:emp.special_order_application.view')->only(['index', 'create', 'show']);
        $this->middleware('permission:emp.special_order_application.apply')->only(['create', 'store']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->datatable($this->applicationService->getRawData('special_order'));
        }

        return view('employee.pages.special-order.index');
    }

    public function create()
    {
        $data = $this->applicationService->getData(['leave', 'offset', 'obs', 'special_order', 'lto']);

        return view('employee.pages.special-order.create', [
            'applications' => $data['applications'],
        ]);
    }

    public function store(StoreSpecialOrderRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user()->load('employeeInformation');
        $employeeNo = $user->employeeInformation->employee_no;

        DB::beginTransaction();

        try {
            $applicationId = DB::table('special_order_applications')->insertGetId([
                'name' => $user->name,
                'user_id' => $user->id,
                'employee_no' => $employeeNo,
                'so_no' => $validated['so_no'],
                'within_metro_manila' => $validated['within_metro_manila'] === 'yes',
                'isHazardous' => $validated['is_hazardous'] === 'yes',
                'status' => 'pending',
                'remarks' => $validated['remarks'] ?? null,
                'level' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('special_order_dates')->insert([
                'special_order_application_id' => $applicationId,
                'date' => $validated['date'],
                'shift' => $validated['shift'],
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $attachmentPath = $file->store('users/' . $employeeNo . '/so-attachments/', 'public');

                DB::table('special_order_attachments')->insert([
                    'special_order_application_id' => $applicationId,
                    'file_path' => $attachmentPath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Special order application has been submitted',
                'redirect' => route('special-order.create'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'store failed',
            ], 500);
        }
    }

    public function show(int $id)
    {
        $data = $this->applicationService->getRawData('special_order', $id)[0] ?? [];

        if (!$data) {
            return redirect()->route('special-order.index');
        }

        return response(['data' => $data, 'status' => 'success'], 200);
    }

    public function destroy(int $id)
    {
        DB::table('special_order_applications')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'success']);
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date', function ($row) {
                return formatDateRanges($row->dates);
            })
            ->addColumn('status_badge', function ($row) {
                $badgeClass = match (strtolower($row->status)) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'dark',
                    'cancelled' => 'danger',
                    default => 'info',
                };

                return '<span class="badge rounded-pill bg-' . $badgeClass . '">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $buttons = '
                    <div class="d-flex">
                        <button data-id="' . $row->id . '"
                            class="btn btn-primary btn-sm ms-1 show-button"
                            title="Show">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                ';

                if ($row->status === 'pending') {
                    $buttons .= '
                        <button data-id="' . $row->id . '"
                            class="btn btn-danger btn-sm ms-1 cancel-button"
                            title="Cancel">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    ';
                }

                return $buttons . '</div>';
            })
            ->rawColumns(['actions', 'status_badge', 'date'])
            ->make(true);
    }
}
