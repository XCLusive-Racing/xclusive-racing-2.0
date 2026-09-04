<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewTeamApplication;
use App\Models\TeamApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = TeamApplication::orderBy('created_at', 'desc')->get();

        return view('admin.applications.index', compact('applications'));
    }

    public function show(TeamApplication $application)
    {
        if (!$application->viewed_at) {
            $application->update(['viewed_at' => now()]);
        }

        return view('admin.applications.show', compact('application'));
    }

    // Manually re-sends the application to the shared team inbox so an admin
    // can pick it up and reply to the applicant from there.
    public function sendToInbox(TeamApplication $application): RedirectResponse
    {
        try {
            Mail::to('info@xclusiveracing.com')->send(new NewTeamApplication($application));
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not send the application — ' . $e->getMessage());
        }

        return back()->with('success', 'Application sent to info@xclusiveracing.com.');
    }
}
