<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\Timelog;
use Illuminate\Http\Request;

class TimelogVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.timelog-verification.view')->only('index');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $timelogs = Timelog::query()
            ->verificationListing()
            ->search($validated['search'] ?? null)
            ->dateRange($validated['from_date'] ?? null, $validated['to_date'] ?? null)
            ->orderByDesc('timelogs.date_time')
            ->orderByDesc('timelogs.id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.timekeeping.timelog-verification.index', [
            'timelogs' => $timelogs,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'from_date' => $validated['from_date'] ?? '',
                'to_date' => $validated['to_date'] ?? '',
            ],
            'fnLabels' => Timelog::FN_LABELS,
        ]);
    }
}
