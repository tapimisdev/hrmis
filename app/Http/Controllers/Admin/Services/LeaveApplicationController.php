<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeaveApplicationController extends Controller {

    public function index() {

        if (request()->ajax()) {
            $query = DB::table('leave_applications')->get();
            return $this->datatable($query);
        }

        return view('admin.pages.services.leave.index');
    }
    
    public function show(int $id) {

        $data = DB::table('leave_applications')
            ->leftJoin('leaves', 'leaves.id', '=', 'leave_applications.leave_id')
            ->where('leave_applications.id', $id)
            ->select([
                'leave_applications.*',
                'leaves.name as leave_name',
                'leaves.id as leave_id'
            ])
            ->first();

        return view('admin.pages.services.leave.show', compact('data'));
      
    }

    public function rules() {
        return [
            'id' => 'required|exists:leave_applications,id',
            'action' => 'required|in:approve,decline'
        ];
    }

    public function save(Request $request) {
        
        $this->validate();

        $payload = $request->all();

        switch($payload['action']) {
            case 'approve':
                $this->approve($payload);
                return;
            case 'decline':
                $this->decline($payload);
                return;
            default:

                break;
        }


    }

    public function approve(array $payload) {

        try {

            DB::table('leave_applications')
                ->where('id', $payload['id'])
                ->update([
                    'status' => 'approved'
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been approved!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ]);
        }




    }

    public function decline(array $payload) {
        
        try {
            
            DB::table('leave_applications')
                ->where('id', $payload['id'])
                ->update([
                    'remarks' => $payload['remarks'] ?? null,
                    'status' => 'rejected',
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application was declined successfully'
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ]);
        }

    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('dates', function($row) {
                return Carbon::parse($row->start_date)->format('M d, Y') . ' - ' . Carbon::parse($row->end_date)->format('M d, Y');
            })
            ->editColumn('type', function($row) {
                $name = DB::table('leaves')
                    ->where('id', $row->leave_id)
                    ->value('name');

                return $name;
            })
            ->addColumn('actions', function ($row) {
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="'.route('services.leaves.show', ['application' => $row->id]).'" 
                            class="btn btn-outline-primary btn show-button ms-1 my-1" 
                            title="Show">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

}