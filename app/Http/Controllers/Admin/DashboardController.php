<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.dashboard.view')->only('index');
    }

    public function index()
    {
        $user = Auth::user();

        return view('admin.pages.dashboard.index', compact('user'));
    }
}
