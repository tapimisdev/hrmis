<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MessagesController extends Controller
{
    public function index()
    {
        return view('admin.pages.messages.index');
    }
}
