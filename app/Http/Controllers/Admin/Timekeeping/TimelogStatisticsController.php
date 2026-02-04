<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DailyTimeRecordService;

class TimelogStatisticsController extends Controller
{

    protected $dtrService; 

    public function __construct(DailyTimeRecordService $dtrService ) {
        $this->dtrService = $dtrService;
    }

    public function index(Request $request) {

        return view('admin.pages.timekeeping.statistics');

    }
}
