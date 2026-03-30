<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Trails;
use Yajra\DataTables\Facades\DataTables;

class TrailsController extends Controller
{
    public function __construct()
    {
        // Ensure user is authenticated and has the correct permission
        $this->middleware(['auth', 'can:view-audit-trails']);
    }

    /**
     * Display the audit trails page.
     */
    public function index(Request $request)
    {
        $query = Trails::query()->latest(); 

        if ($request->ajax()) {
            return $this->datatable($query);
        }

        return view('admin.pages.trails.index');
    }

    protected function datatable($query)
    {
        return DataTables::of($query)
            ->editColumn('created_at', function ($trail) {
                return $trail->created_at ? $trail->created_at->format('F d, Y \a\t h:i:s A') : '-';
            })
            ->editColumn('updated_at', function ($trail) {
                return $trail->updated_at ? $trail->updated_at->format('F d, Y \a\t h:i:s A') : '-';
            })
            ->addColumn('user', function ($trail) {
                return $trail->name ?? 'System';
            })
            ->editColumn('payload', function($trail) {
                return 
                '<pre style="max-width:400px;white-space:pre-wrap;">'
                . json_encode($trail->payload, JSON_PRETTY_PRINT)
                . '</pre>';
            })
            ->rawColumns(['payload'])
            ->make(true);
    }
}