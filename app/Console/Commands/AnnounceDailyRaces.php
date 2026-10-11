<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\NormalizesDiscordRoleId;
use App\Models\Race;
use App\Models\TeamEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnnounceDailyRaces extends Command
{
    use NormalizesDiscordRoleId;

    protected $signature = 'races:announce-daily {--dry-run : Build the message and log it instead of posting to Discord} {--force : Post even if already announced today}';

    protected $description = 'Posts upcoming races in the next 24h to Discord';

    private const CACHE_KEY = 'races_announce_daily_last_run';

    public function handle(): int
    {
        $today = now('Europe/London')->toDateString();

        if (! $this->option('force') && ! $this->option('dry-run') && Cache::get(self::CACHE_KEY) === $today) {
            $this->info("Already announced today ({$today}) — skipping. Use --force to post again.");

            return self::SUCCESS;
        }

        $start = now();
        $end = now()->addDay();

        $races = Race::where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<', $end)
            ->where('status', '!=', 'finished')
            ->withCount(['registrations', 'teamEntries'])
            ->orderBy('scheduled_at')
            ->get();

        $teamEvents = TeamEvent::where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->orderBy('starts_at')
            ->get();

        $embed = $this->buildEmbed($races, $teamEvents, $this->featuredEvents($start));

        if ($this->option('dry-run')) {
            Log::info('races:announce-daily dry-run', ['embed' => $embed]);
            $this->info('Dry run — message built and logged instead of sent:');
            $this->line(json_encode($embed, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $webhook = config('services.discord.announcer_webhook');
        $roleId = $this->normalizeRoleId(config('services.discord.xcl_member_role_id'));

        if (! $webhook || ! $roleId) {
            $this->error('DISCORD_ANNOUNCER_WEBHOOK / DISCORD_XCL_MEMBER_ROLE_ID are not configured.');

            return self::FAILURE;
        }

        $response = Http::post($webhook, [
            'content' => "<@&{$roleId}>",
            'embeds' => [$embed],
            'allowed_mentions' => [
                'roles' => [$roleId],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('races:announce-daily webhook failed', ['status' => $response->status(), 'body' => $response->body()]);
            $this->error('Discord webhook failed: '.$response->status());

            return self::FAILURE;
        }

        Cache::put(self::CACHE_KEY, $today, now()->addDay());
        $this->info('Daily race announcement posted to Discord.');

        return self::SUCCESS;
    }

    // Races a channel announcement highlights besides the most popular one: anything with
    // more than this many real sign-ups.
    private const BUSY_RACE_SIGNUPS = 10;

    // How far ahead the next weekly / monthly event is looked for.
    private const WEEKLY_LOOKAHEAD_DAYS = 8;

    private const MONTHLY_LOOKAHEAD_DAYS = 45;

    // The next Weekly Event (Race::isWeeklyEvent()) and the next monthly one — a Special
    // Event, Race::isSpecialEvent() — of each game: the same picks as the Events page's
    // featured row, whether or not they start in the next 24 hours.
    private function featuredEvents($from): array
    {
        $upcoming = fn (int $days) => Race::where('scheduled_at', '>=', $from)
            ->where('scheduled_at', '<', $from->copy()->addDays($days))
            ->where('status', '!=', 'finished')
            ->withCount(['registrations', 'teamEntries'])
            ->orderBy('scheduled_at');

        $firstPerGame = fn ($races) => $races->unique('game')->values();

        return [
            'weekly' => $firstPerGame($upcoming(self::WEEKLY_LOOKAHEAD_DAYS)
                ->whereNull('championship_id')->where('is_championship', false)
                ->get()->filter(fn (Race $r) => $r->isWeeklyEvent())),
            'monthly' => $firstPerGame($upcoming(self::MONTHLY_LOOKAHEAD_DAYS)->specialEvents()->get()),
        ];
    }

    // A short post instead of every race of the day (it got too long): one line with how many
    // races there are and where today's Daily Race runs, then the race with the most real
    // sign-ups plus any other with more than BUSY_RACE_SIGNUPS, in start-time order (with no
    // sign-ups anywhere yet, the next race). Below that the Weekly Event and the Monthly
    // Event, with their date. Special team events are always listed.
    private function buildEmbed($races, $teamEvents, array $featured): array
    {
        $fields = [];

        // The weekly and monthly events have their own lines below.
        $featuredIds = $featured['weekly']->merge($featured['monthly'])->pluck('id');
        $candidates = $races->reject(fn (Race $r) => $featuredIds->contains($r->id));

        $popular = $candidates->filter(fn (Race $r) => $r->realSignupCount() > 0)
            ->sortByDesc(fn (Race $r) => $r->realSignupCount())
            ->take(1);
        $busy = $candidates->filter(fn (Race $r) => $r->realSignupCount() > self::BUSY_RACE_SIGNUPS);
        $highlighted = $popular->merge($busy)->unique('id')->sortBy('scheduled_at');
        if ($highlighted->isEmpty()) {
            $highlighted = $candidates->take(1);
        }

        foreach ($highlighted as $race) {
            $fields[] = $this->raceField("🏎️ {$race->title} — {$race->track}", $race);
        }

        foreach (['weekly' => '📆 Weekly Event', 'monthly' => '🏆 Monthly Event'] as $slot => $label) {
            foreach ($featured[$slot] as $race) {
                $fields[] = $this->raceField("{$label}: {$race->title} — {$race->track}", $race, withDate: true);
            }
        }

        foreach ($teamEvents as $event) {
            $name = $event->subtitle ? "⭐ {$event->title} — {$event->subtitle}" : "⭐ {$event->title}";
            $bits = ['🕐 '.$event->starts_at->timezone('Europe/London')->format('g:iA T')];

            $driverCount = $event->participatingDrivers()->count();
            if ($driverCount > 0) {
                $bits[] = "👥 {$driverCount} ".($driverCount === 1 ? 'driver' : 'drivers');
            }

            $fields[] = [
                'name' => $name,
                'value' => implode("\n", $bits),
                'inline' => false,
            ];
        }

        if ($fields) {
            $dailyTracks = $races->where('event_tag', 'daily')->pluck('track')->filter()->unique();
            $lines = [$races->count().' '.($races->count() === 1 ? 'race' : 'races').' in the next 24 hours.'];
            if ($dailyTracks->isNotEmpty()) {
                $lines[] = '📅 Daily Race: **'.$dailyTracks->implode(' · ').'**';
            }
            $lines[] = 'Full schedule: '.route('events.index');
            if ($highlighted->isNotEmpty()) {
                $lines[] = '';
                $lines[] = $popular->isNotEmpty() ? '🔥 **Most popular today**' : '⏭️ **Next up**';
            }
            $description = implode("\n", $lines);
        } else {
            $description = 'No scheduled events in the next 24 hours. Check back tomorrow!';
        }

        return [
            'title' => "🏁 Today's Race Schedule",
            'description' => $description,
            'color' => 8134621, // #7c1fdd
            'fields' => $fields,
            'footer' => ['text' => 'XCLusive Racing • xclusiveracing.com'],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    // One race as an embed field: start time (with the date for an event that isn't today's),
    // game, SR requirement and sign-ups.
    private function raceField(string $name, Race $race, bool $withDate = false): array
    {
        $uk = $race->scheduledAtUk();
        $bits = [($withDate ? '🗓️ '.$uk->format('D j M').' · ' : '').'🕐 '.$uk->format('g:iA T'), '🎮 '.$this->gameLabel($race->game)];

        if ($race->sr_requirement) {
            $bits[] = '🛡️ SR '.$race->sr_requirement.'.0+';
        }

        $signups = $race->is_endurance ? $race->team_entries_count : $race->displayedSignupCount();
        if ($signups > 0) {
            $bits[] = "👥 {$signups}".($race->max_drivers ? " / {$race->max_drivers}" : '').' signed up';
        }

        return ['name' => $name, 'value' => implode("\n", $bits), 'inline' => false];
    }

    private function gameLabel(string $game): string
    {
        return match ($game) {
            'acc' => 'ACC',
            'lmu' => 'Le Mans Ultimate',
            'iracing' => 'iRacing',
            'ac' => 'ACC PC',
            default => strtoupper($game),
        };
    }
}
