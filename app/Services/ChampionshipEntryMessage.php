<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\Message;
use App\Models\Race;
use App\Models\User;
use App\Services\Contracts\ServerConfigGenerator;

// The inbox message a championship entry gets once it counts (registered without
// manual approval, or approved by the league): the entry itself, how the automatic
// round entry works, and every upcoming round with its server name and password —
// since nobody signs up per round any more, this is where the server details come
// from. A team car's message goes to every driver of the car (and whoever entered it).
// A pending entry gets nothing here; a waitlisted one gets a short note without servers.
class ChampionshipEntryMessage
{
    public const TYPE = 'championship_entry';

    private const TBA = 'To be announced';

    public function __construct(private ServerConfigGenerator $config) {}

    public function send(ChampionshipRegistration $registration, Championship $championship, string $title): void
    {
        $body = $this->body($registration, $championship);

        foreach ($this->recipientIds($registration) as $userId) {
            Message::create([
                'user_id' => $userId,
                'title' => $title.': '.$championship->name,
                'body' => $body,
                'type' => self::TYPE,
                'related_id' => $championship->id,
                'related_type' => Championship::class,
            ]);
        }
    }

    public function body(ChampionshipRegistration $registration, Championship $championship): string
    {
        if (! $registration->is_spectator && $registration->user && $championship->isRegistrationWaitlisted($registration->user)) {
            return "{$championship->name} is full, so your entry is on the waiting list.\n\n"
                ."As soon as a spot opens up you're moved in and entered into every upcoming round automatically. "
                .'Keep an eye on the championship page for your place in the queue.';
        }

        $lines = ["Welcome to {$championship->name}!", '', 'YOUR ENTRY'];
        array_push($lines, ...$this->entryLines($registration));

        $lines[] = '';
        if ($registration->is_spectator) {
            $lines[] = "You're registered as a spectator: join each round through the server's spectator slots with the spectator password below.";
        } else {
            $lines[] = "You're entered into every upcoming round automatically — no need to sign up for each round. "
                ."Can't make one? Open that round's event page and unregister from it; you stay in the championship.";
        }

        $lines[] = '';
        $lines[] = 'ROUNDS & SERVERS';
        $rounds = $this->upcomingRounds($championship);
        if ($rounds->isEmpty()) {
            $lines[] = 'No rounds are scheduled yet — they appear on the championship page once the league adds them.';
        }
        foreach ($rounds as $race) {
            array_push($lines, '', ...$this->roundLines($race, (bool) $registration->is_spectator));
        }

        $lines[] = '';
        $lines[] = "Server details can still change before a round — the round's event page always shows the latest.";
        $lines[] = '';
        $lines[] = 'See you on track!';

        return implode("\n", $lines);
    }

    private function entryLines(ChampionshipRegistration $registration): array
    {
        if ($registration->is_spectator) {
            return ['Spectator'];
        }

        $lines = [];
        if ($registration->racingTeam) {
            $lines[] = 'Team: '.$registration->racingTeam->name;
            $drivers = User::whereIn('id', $registration->driverIds())->get()->map->displayName()->filter();
            if ($drivers->isNotEmpty()) {
                $lines[] = 'Drivers: '.$drivers->implode(', ');
            }
            if ($registration->startingDriver) {
                $lines[] = 'Starting driver: '.$registration->startingDriver->displayName();
            }
            if ($registration->reserveDriver) {
                $lines[] = 'Reserve: '.$registration->reserveDriver->displayName();
            }
        }

        $car = trim(($registration->car_number !== null ? '#'.$registration->car_number.' ' : '').($registration->car_model ?? ''));
        if ($car !== '') {
            $lines[] = 'Car: '.$car;
        }
        if ($registration->championshipClass) {
            $lines[] = 'Class: '.$registration->championshipClass->name;
        }
        if ($registration->driverClass) {
            $lines[] = 'Driver class: '.$registration->driverClass->name;
        }

        return $lines ?: ['Driver: '.($registration->user?->displayName() ?? '—')];
    }

    private function roundLines(Race $race, bool $spectator): array
    {
        $settings = $race->ftpServer ? $this->config->settings($race, $race->ftpServer) : [];
        $password = $spectator ? ($settings['spectatorPassword'] ?? null) : ($settings['password'] ?? null);

        return [
            ($race->round_number ? 'Round '.$race->round_number.' · ' : '').$race->track
                .' · '.$race->scheduledAtUk()->format('D j M Y, H:i T'),
            'Server: '.($settings['serverName'] ?? self::TBA),
            ($spectator ? 'Spectator password: ' : 'Password: ').($password ?: self::TBA),
        ];
    }

    private function upcomingRounds(Championship $championship)
    {
        // A server belongs to the league (TenantScope), and the driver isn't a member.
        return $championship->rounds()
            ->where('status', 'open')->where('scheduled_at', '>', now())
            ->with(['ftpServer' => fn ($q) => $q->withoutTenantScope()])
            ->get();
    }

    private function recipientIds(ChampionshipRegistration $registration): array
    {
        $ids = $registration->racing_team_id && ! $registration->is_spectator ? $registration->driverIds() : [];
        $ids[] = $registration->user_id;

        return array_values(array_unique(array_filter($ids)));
    }
}
