<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class TaxationController extends Controller
{
    public function index() 
    {
        return view('admin.pages.taxation.taxation.index');
    }
}
