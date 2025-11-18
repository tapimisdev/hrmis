<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class TrancheController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.tranche.view')->only(['index', 'show']);
        $this->middleware('permission:hr.tranche.create')->only(['create', 'store']);
        $this->middleware('permission:hr.tranche.edit')->only('edit', 'update');
        $this->middleware('permission:hr.tranche.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        if($request->ajax()) {
            $query = DB::table('tranche')
                ->get();
                
            return $this->datatable($query); 
        }

        return view('admin.pages.settings.tranche.index');
    }

    public function show($id)
    {
        $tranche = DB::table('tranche')->where('id', $id)->first();
        $items = DB::table('tranche_items')
            ->where('tranche_id', $id)
            ->orderBy('salary_grade')
            ->get();

        return view('admin.pages.settings.tranche.show', compact('tranche', 'items'));
    }


    public function create()
    {

        $isEdit = false;
        $id = null;

        $employment_types = DB::table('employment_types')->get();
    
        return view('admin.pages.settings.tranche.form', compact('isEdit', 'id', 'employment_types'));
    }

    public function store(Request $request) 
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'employment_type_id'   => 'required|exists:employment_types,id',
            'date'              => 'required|date',
            'file'              => 'required|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'field error',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $file = $request->file('file');
            $path = $file->getRealPath();
            $fileData = array_map('str_getcsv', file($path));

            $header = array_map(function($h) {
                return strtolower(str_replace(' ', '_', trim($h)));
            }, $fileData[0]);

            $rows = array_slice($fileData, 1);

            $data = [];
            foreach ($rows as $row) {
                if (count($row) == count($header)) {
                    $data[] = array_combine($header, $row);
                }
            }

            $tranche_id = DB::table('tranche')->insertGetId([
                'employment_type_id' => $request->employment_type_id,
                'date'        => $request->date,
                'description' => $request->description,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($data as $items) {
                DB::table('tranche_items')->insert([
                    'tranche_id'    => $tranche_id,
                    'salary_grade'  => $items['salary_grade'],
                    'step_1'        => $items['step_1'] ?? null,
                    'step_2'        => $items['step_2'] ?? 0,
                    'step_3'        => $items['step_3'] ?? 0,
                    'step_4'        => $items['step_4'] ?? 0,
                    'step_5'        => $items['step_5'] ?? 0,
                    'step_6'        => $items['step_6'] ?? 0,
                    'step_7'        => $items['step_7'] ?? 0,
                    'step_8'        => $items['step_8'] ?? 0,
                ]);
            }

            DB::commit();

           return response()->json([
                'status' => 'success',
                'message' => 'Tranche ' . strtoupper($request->name) . ' Added',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(int $id)
    {

        $data = DB::table('tranche')
            ->where('id', $id)
            ->first();

        $items = DB::table('tranche_items')
            ->where('tranche_id', $id)
            ->orderBy('salary_grade')
            ->get();

        $data->items = $items;

        $employment_types = DB::table('employment_types')->get();

        $isEdit = true;

        return view('admin.pages.settings.tranche.form', compact('isEdit', 'id', 'data', 'employment_types'));
    }

    public function update(Request $request, $id) 
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'employment_type_id'   => 'required|exists:employment_types,id|unique:tranche,id',
            'date' => 'required|date',
            'file' => 'nullable|mimes:csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'field error',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            DB::table('tranche')
                ->where('id', $id)
                ->update([
                    'employment_type_id' => $request->employment_type_id,
                    'date'        => $request->date,
                    'description' => $request->description,
                    'updated_at'  => now(),
                ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->getRealPath();
                $fileData = array_map('str_getcsv', file($path));

                $header = array_map(function($h) {
                    return strtolower(str_replace(' ', '_', trim($h)));
                }, $fileData[0]);

                $rows = array_slice($fileData, 1);

                $data = [];
                foreach ($rows as $row) {
                    if (count($row) == count($header)) {
                        $data[] = array_combine($header, $row);
                    }
                }

                DB::table('tranche_items')->where('tranche_id', $id)->delete();

                foreach ($data as $items) {
                    DB::table('tranche_items')->insert([
                        'tranche_id'    => $id,
                        'salary_grade'  => $items['salary_grade'],
                        'step_1'        => $items['step_1'] ?? null,
                        'step_2'        => $items['step_2'] ?? 0,
                        'step_3'        => $items['step_3'] ?? 0,
                        'step_4'        => $items['step_4'] ?? 0,
                        'step_5'        => $items['step_5'] ?? 0,
                        'step_6'        => $items['step_6'] ?? 0,
                        'step_7'        => $items['step_7'] ?? 0,
                        'step_8'        => $items['step_8'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tranche ' . strtoupper($request->name) . ' Updated',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id) {

        DB::beginTransaction();

        try {
            
            DB::table('tranche_items')->where('tranche_id', $id)->delete();
            DB::table('tranche')->where('id', $id)->delete();
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'tranche has been deleted.',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while deleting the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                $employment_type = $row->id;
                $name = DB::table('employment_types')
                    ->where('id', $row->employment_type_id)
                    ->value('name');

                return $name;
            })
            ->editColumn('date', function ($row) {
                if (empty($row->date)) {
                    return '-';
                }

                try {
                    return \Carbon\Carbon::parse($row->date)->format('F j, Y');
                } catch (\Exception $e) {
                    return $row->date;
                }
            })
            ->addColumn('actions', function ($row) {
                $showRoute = route('settings.tranche.show', [
                    'id' => $row->id
                ]);
                $editRoute = route('settings.tranche.edit', [
                    'id' => $row->id
                ]);
                $deleteRoute = route('settings.tranche.destroy', [
                    'id' => $row->id
                ]);
                
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <button id="btn-show" data-target="'.$showRoute.'"
                            class="btn btn-primary btn ms-1 my-1" 
                            title="Show">
                                <i class="fa-solid fa-eye"></i>
                        </button>
                        <a href="' . $editRoute . '" 
                            class="btn btn-secondary btn ms-1 my-1" 
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button id="btn-delete"
                            class="btn btn-danger btn ms-1 my-1" 
                            data-target="' . $deleteRoute . '"
                            title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    #API
    public function tranches()
    {
        $query = DB::table('tranche')
                ->get();

        return response(['data' => $query, 'status' => 'success'], 200);
    }

    public function compute_salary($tranch_id, $salary_grade, $step = null) 
    {
        $daily_rate = null;
        $total_salary = null;
        try {
            $db_tranche_item = DB::table('tranche_items')
                        ->where('tranche_id', $tranch_id)
                        ->where('salary_grade', $salary_grade)
                        ->first();

            if ($db_tranche_item) {

                if ($step === null || $step === '' || $step === 'null') {
                    $stepColumn = 'step_1';
                } else {
                    $stepColumn = 'step_' . $step;
                }

                $total_salary = $db_tranche_item->{$stepColumn} ?? null;

                $total_salary = $total_salary ? (float) str_replace(',', '', $total_salary) : null;

                $daily_rate = $total_salary !== null ? round($total_salary / 22, 2) : null;

            }

            $data = [
                'daily_rate' => $daily_rate,
                'total_salary' => $total_salary
            ];

            return response(['data' => $data, 'status' => 'success'], 200);

        } catch (\Exception $e) {
            return response(['message' => $e->getMessage(), 'status' => 'compute fail'], 500);
        }

    }
}
