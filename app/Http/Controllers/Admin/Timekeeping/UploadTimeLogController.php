<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TimelogImport;
use Illuminate\Support\Facades\DB;

class UploadTimeLogController extends Controller
{
    public function index()
    {
        return view('admin.pages.timekeeping.upload-timelog.index');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shift' => 'required|exists:shifts,id',
            'schedule' => 'required|exists:work_schedule,id',
            'file' => 'required|file|mimes:xlsx,xls|max:10240' 
        ]);

        DB::beginTransaction();

        try {

            Excel::import(new TimelogImport(
                $validatedData['shift'],
                $validatedData['schedule']
            ), $validatedData['file']);

            DB::commit();
            
            return response(['message' => 'imported succesfully', 'status' => 'success'], 200);

        } catch (\Exception $e) {

            DB::rollback();

            return response(['data' => $e->getMessage(), 'status' => 'store failed'], 500);

        }
    }
}
