<?php

namespace App\Services;

use App\Models\Bop;
use App\Models\Race;

class AccServerConfigService
{
    public function entryList(Race $race): array
    {
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

        $entries = $registrations->map(function ($reg) use ($race) {
            $user      = $reg->user;
            $lastName  = $user->team ? $user->name . "\n" . $user->team : ($user->name ?? '');
            $shortName = strtoupper(substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3));

            return [
                'drivers' => [
                    [
                        'firstName'      => '',
                        'lastName'       => $lastName,
                        'shortName'      => $shortName,
                        'playerID'       => $user->platform_id ?? '',
                        'driverCategory' => $user->ratingClass($race->game),
                    ],
                ],
                'raceNumber'          => is_numeric($user->car_number) ? (int) $user->car_number : 0,
                'defaultGridPosition' => -1,
                'ballastKg'           => 0,
                'forcedCarModel'      => -1,
                'overrideDriverInfo'  => 1,
            ];
        })->values()->all();

        return [
            'entries'        => $entries,
            'configVersion'  => 1,
            'forceEntryList' => 1,
        ];
    }

    public function configuration(Race $race): array
    {
        $sessions = [];
        $hour     = $this->startHour($race->time_of_day);

        if ($race->practice_duration) {
            $sessions[] = [
                'hourOfDay'               => $hour,
                'dayOfWeekend'            => 1,
                'timeMultiplier'          => 1,
                'sessionType'             => 'P',
                'sessionDurationMinutes'  => (int) $race->practice_duration,
            ];
            $hour = min($hour + 1, 23);
        }

        if ($race->qualifying_duration) {
            $sessions[] = [
                'hourOfDay'               => $hour,
                'dayOfWeekend'            => 1,
                'timeMultiplier'          => 1,
                'sessionType'             => 'Q',
                'sessionDurationMinutes'  => (int) $race->qualifying_duration,
            ];
            $hour = min($hour + 1, 23);
        }

        $sessions[] = [
            'hourOfDay'               => $hour,
            'dayOfWeekend'            => 1,
            'timeMultiplier'          => 1,
            'sessionType'             => 'R',
            'sessionDurationMinutes'  => (int) ($race->race_duration ?? 20),
        ];

        [$rain, $cloudLevel, $weatherRandomness] = $this->weatherParams($race->weather);

        return [
            'track'                    => $this->trackSlug($race->track),
            'preRaceWaitingTimeSeconds'=> 60,
            'sessionOverTimeSeconds'   => 120,
            'ambientTemp'              => 22,
            'cloudLevel'               => $cloudLevel,
            'rain'                     => $rain,
            'weatherRandomness'        => $weatherRandomness,
            'sessions'                 => $sessions,
            'configVersion'            => 1,
        ];
    }

    public function settings(Race $race): array
    {
        $srRequired = match ($race->sr_requirement) {
            '5'  => 5,
            '7'  => 7,
            default => -1,
        };

        $rcRequired = match ($race->min_rating) {
            'bronze'   => 0,
            'silver'   => 60,
            'gold'     => 80,
            'platinum' => 95,
            'alien'    => 99,
            default    => -1,
        };

        return [
            'serverName'              => 'XCL | ' . ($race->title ?? 'Event'),
            'adminPassword'           => '',
            'carGroup'                => $this->carGroup($race->car_class),
            'trackMedal'              => 0,
            'safetyRatingRequired'    => $srRequired,
            'racecraftRatingRequired' => $rcRequired,
            'password'                => '',
            'spectatorPassword'       => '',
            'maxCarSlots'             => $race->max_drivers ?? 30,
            'dumpLeaderboards'        => 0,
            'isRaceLocked'            => 1,
            'randomizeTrackWhenEmpty' => 0,
            'centralEntryListPath'    => '',
            'allowAutoDQ'             => 0,
            'shortFormationLap'       => 0,
            'dumpEntryList'           => 0,
            'formationLapType'        => 3,
            'configVersion'           => 1,
        ];
    }

    public function bop(string $game = 'acc'): array
    {
        $entries = Bop::where('game', $game)->where('active', true)->orderBy('car_model')->get();

        $mapped = [];
        foreach ($entries as $bop) {
            $carId = Bop::carModelId($bop->car_model);
            if ($carId === null) {
                continue;
            }

            $mapped[] = [
                'track'      => $bop->track ?? '',
                'carModel'   => $carId,
                'ballastKg'  => (int) $bop->ballast_kg,
                'restrictor' => (int) $bop->restrictor,
            ];
        }

        return [
            'entries'       => $mapped,
            'configVersion' => 1,
        ];
    }

    private function startHour(?string $timeOfDay): int
    {
        return match ($timeOfDay) {
            'dusk'    => 17,
            'night'   => 21,
            'dynamic' => 10,
            default   => 14,
        };
    }

    private function weatherParams(?string $weather): array
    {
        // [rain, cloudLevel, weatherRandomness]
        return match ($weather) {
            'wet'    => [0.8, 0.8, 2],
            'mixed'  => [0.3, 0.5, 3],
            'random' => [0.0, 0.5, 4],
            default  => [0.0, 0.1, 1],
        };
    }

    private function trackSlug(string $track): string
    {
        return strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($track)));
    }

    private function carGroup(?string $carClass): string
    {
        if (!$carClass) return 'GT3';

        return match (strtoupper(trim($carClass))) {
            'GT4'              => 'GT4',
            'GTE'              => 'GTE',
            'LMP2', 'LMP 2'   => 'LMP2',
            'CUP', 'PORSCHE CUP', 'GT3 CUP' => 'CUP',
            'ST', 'SUPER TROFEO' => 'ST',
            'CHL', 'CHALLENGE'   => 'CHL',
            'TCX', 'TCR'         => 'TCX',
            default              => 'GT3',
        };
    }
}