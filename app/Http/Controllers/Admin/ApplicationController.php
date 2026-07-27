<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamApplication;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = TeamApplication::orderBy('created_at', 'desc')->get();

        return view('admin.applications.index', compact('applications'));
    }

    public function show(TeamApplication $application)
    {
        return view('admin.applications.show', compact('application'));
    }
}
