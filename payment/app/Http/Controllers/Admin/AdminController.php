<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminController extends Controller
{

    public function dashboard(): View
    {
        return view('admin.pages.home');
    }

    public function api_docs(): View
    {
        return view('admin.pages.api_docs');
    }
}
