<?php

namespace App\Console\Commands;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Console\Command;

// A solo championship entrant still signs up for each round on its own event
// page — a championship registration doesn't enter them into the rounds. This
// reminds the ones who haven't, once per round, through the site inbox, in the
// run-up to the round while its sign-up is still open.
class RemindRoundSignups extends Command
{
    public const MESSAGE_TYPE = 'round_signup_reminder';

    // How far ahead of a round the reminder goes out.
    public const HOURS_AHEAD = 48;

    protected $signature = 'championships:remind-round-signups {--dry-run : List who would be reminded without sending anything}';

    protected $description = 'Inbox-reminds championship entrants who have not signed up for an upcoming round yet';

    public function handle(): int
    {
        $rounds = Race::whereNotNull('championship_id')
            ->where('status', 'open')
            ->whereBetween('scheduled_at', [now()->addMinutes(5), now()->addHours(self::HOURS_AHEAD)])
            ->get();

        $sent = 0;

        foreach ($rounds as $round) {
            // Tenant scope off: this runs with no user, so it would hide every championship.
            $championship = Championship::withoutTenantScope()->publiclyVisible()->find($round->championship_id);
            if (! $championship) {
                continue;
            }

            foreach (User::whereIn('id', $this->entrantsToRemind($championship, $round))->get() as $user) {
                // A suspended driver can't sign up anyway.
                if ($user->isSuspended()) {
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("Would remind user {$user->id} about race {$round->id} ({$round->title})");

                    continue;
                }

                Message::create([
                    'user_id' => $user->id,
                    'title' => 'Sign up for '.$round->title,
                    'body' => "You're entered in {$championship->name}, but you haven't signed up for this round yet."
                        ."\n\n{$round->title} starts ".$this->startTimeFor($round, $user).'.'
                        ." Sign up on the event page, otherwise you won't be on the server's entry list.",
                    'type' => self::MESSAGE_TYPE,
                    'related_id' => $round->id,
                    'related_type' => Race::class,
                ]);
                $sent++;
            }
        }

        $this->info($this->option('dry-run') ? 'Dry run — nothing sent.' : "{$sent} reminder(s) sent.");

        return self::SUCCESS;
    }

    // In the driver's own timezone and clock preference (UK time if they never set one).
    private function startTimeFor(Race $round, User $user): string
    {
        return $round->scheduled_at->copy()
            ->timezone($user->timezone ?: 'Europe/London')
            ->format($user->uses_12_hour_clock ? 'D j M, g:i A T' : 'D j M, H:i T');
    }

    // Approved solo drivers (not spectators, not team entries — a team signs up
    // per round through its owner) who are neither on the round nor already reminded.
    private function entrantsToRemind(Championship $championship, Race $round): array
    {
        $entrants = ChampionshipRegistration::where('championship_id', $championship->id)
            ->approved()
            ->where('is_spectator', false)
            ->whereNull('racing_team_id')
            ->pluck('user_id');

        $signedUp = RaceRegistration::where('race_id', $round->id)->pluck('user_id');

        $reminded = Message::where('type', self::MESSAGE_TYPE)
            ->where('related_type', Race::class)
            ->where('related_id', $round->id)
            ->pluck('user_id');

        return $entrants->diff($signedUp)->diff($reminded)->unique()->values()->all();
    }
}
