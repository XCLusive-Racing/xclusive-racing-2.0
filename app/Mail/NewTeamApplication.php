<?php

namespace App\Mail;

use App\Models\TeamApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewTeamApplication extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TeamApplication $application)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New Team Application: ' . $this->application->role_label)
            ->view('emails.team-application');
    }
}
