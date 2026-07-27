<?php

namespace App\Http\Controllers;

use App\Mail\NewTeamApplication;
use App\Models\TeamApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TeamApplicationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'discord'       => ['nullable', 'string', 'max:100'],
            'role'          => ['required', 'string', 'in:' . implode(',', array_keys(TeamApplication::ROLES))],
            'platforms'     => ['nullable', 'array'],
            'platforms.*'   => ['string', 'in:PC,PS5,Xbox'],
            'motivation'    => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $application = TeamApplication::create($validated);

        // Notify the team by email — best-effort: an SMTP hiccup shouldn't
        // fail the applicant's submission, which is already saved above.
        try {
            Mail::to('info@xclusiveracing.com')->send(new NewTeamApplication($application));
        } catch (\Throwable $e) {
            Log::warning('Failed to send team application notification email', [
                'application_id' => $application->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return redirect(route('team.join') . '#apply-form')
            ->with('success', 'Thanks! We will be in touch soon.');
    }
}
