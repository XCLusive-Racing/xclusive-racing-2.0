<?php

namespace App\Services;

use App\Models\Bop;
use App\Models\FtpServer;
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
                'hourOfDay'              => $hour,
                'dayOfWeekend'           => 1,
                'timeMultiplier'         => 1,
                'sessionType'            => 'P',
                'sessionDurationMinutes' => (int) $race->practice_duration,
            ];
            $hour = min($hour + 1, 23);
        }

        if ($race->qualifying_duration) {
            $sessions[] = [
                'hourOfDay'              => $hour,
                'dayOfWeekend'           => 1,
                'timeMultiplier'         => 1,
                'sessionType'            => 'Q',
                'sessionDurationMinutes' => (int) $race->qualifying_duration,
            ];
            $hour = min($hour + 1, 23);
        }

        $sessions[] = [
            'hourOfDay'              => $hour,
            'dayOfWeekend'           => 1,
            'timeMultiplier'         => 1,
            'sessionType'            => 'R',
            'sessionDurationMinutes' => (int) ($race->race_duration ?? 20),
        ];

        [$rain, $cloudLevel, $weatherRandomness] = $this->weatherParams($race->weather);

        if ($race->weather_randomness !== null) {
            $wr = $race->weather_randomness;
            $weatherRandomness = $wr === 'random' ? rand(0, 7) : (int) $wr;
        }

        return [
            'track'                     => $this->trackSlug($race->track),
            'preRaceWaitingTimeSeconds' => 60,
            'sessionOverTimeSeconds'    => 120,
            'ambientTemp'               => 22,
            'cloudLevel'                => $cloudLevel,
            'rain'                      => $rain,
            'weatherRandomness'         => $weatherRandomness,
            'sessions'                  => $sessions,
            'configVersion'             => 1,
        ];
    }

    public function settings(Race $race, ?FtpServer $server = null): array
    {
        $base = $server?->settings_defaults ?? $this->defaultSettings();

        $n = $server?->server_number;

        return array_merge($base, [
            'serverName'                 => $n
                ? 'XCL SERVER ' . $n . ' - Daily Sprint - Playstation 5 & Xbox Series S/X'
                : ($base['serverName'] ?? 'XCL SERVER - Daily Sprint - Playstation 5 & Xbox Series S/X'),
            'password'                   => $n ? $n . 'xcl' : ($base['password'] ?? '1xcl'),
            'safetyRatingRequirement'    => $this->srRequired($race),
            'racecraftRatingRequirement' => $this->rcRequired($race),
            'maxCarSlots'                => $race->max_drivers ?? ($base['maxCarSlots'] ?? 30),
            'carGroup'                   => $this->carGroup($race->car_class),
        ]);
    }

    public function eventRules(?FtpServer $server = null): array
    {
        return $server?->eventrules_defaults ?? $this->defaultEventRules();
    }

    public function assistRules(?FtpServer $server = null): array
    {
        return $server?->assistrules_defaults ?? $this->defaultAssistRules();
    }

    public function defaultSettings(): array
    {
        return [
            'serverName'                 => 'XCL SERVER - Daily Sprint - Playstation 5 & Xbox Series S/X',
            'adminPassword'              => '3867cf9b',
            'randomizeTrackWhenEmpty'    => 0,
            'trackMedalsRequirement'     => 0,
            'safetyRatingRequirement'    => -1,
            'racecraftRatingRequirement' => -1,
            'allowAutoDQ'                => 0,
            'password'                   => '1xcl',
            'maxConnections'             => 100,
            'spectatorSlots'             => 2,
            'spectatorPassword'          => 'Password',
            'dumpLeaderboards'           => 1,
            'isCPServer'                 => 0,
            'competitionRatingMin'       => -1,
            'competitionRatingMax'       => -1,
            'configVersion'              => 1,
            'maxCarSlots'                => 30,
            'shortFormationLap'          => 0,
            'dumpEntryList'              => 1,
            'formationLapType'           => 3,
            'region'                     => 'EU',
            'isRaceLocked'               => 0,
            'isCrossplayServer'          => 1,
            'carGroup'                   => 'FreeForAll',
        ];
    }

    public function defaultEventRules(): array
    {
        return [
            'pitWindowLengthSec'                   => 1680,
            'mandatoryPitstopCount'                => 1,
            'qualifyStandingType'                  => 1,
            'isRefuellingAllowedInRace'            => true,
            'isRefuellingTimeFixed'                => false,
            'isMandatoryPitstopRefuellingRequired' => true,
            'isMandatoryPitstopTyreChangeRequired' => false,
            'driverStintTimeSec'                   => -1,
            'maxTotalDrivingTime'                  => -1,
            'maxDriversCount'                      => 1,
            'isMandatoryPitstopSwapDriverRequired' => false,
        ];
    }

    public function defaultAssistRules(): array
    {
        return [
            'disableIdealLine'         => 0,
            'disableAutosteer'         => 1,
            'stabilityControlLevelMax' => 0,
            'disableAutoPitLimiter'    => 0,
            'disableAutoGear'          => 0,
            'disableAutoClutch'        => 0,
            'disableAutoEngineStart'   => 0,
            'disableAutoWiper'         => 0,
            'disableAutoLights'        => 0,
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

    private function srRequired(Race $race): int
    {
        return match ($race->sr_requirement) {
            '5'  => 5,
            '7'  => 7,
            default => -1,
        };
    }

    private function rcRequired(Race $race): int
    {
        return match ($race->min_rating) {
            'bronze'   => 0,
            'silver'   => 60,
            'gold'     => 80,
            'platinum' => 95,
            'alien'    => 99,
            default    => -1,
        };
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
        if (!$carClass) return 'FreeForAll';

        return match (strtoupper(trim($carClass))) {
            'GT3'                              => 'GT3',
            'GT4'                              => 'GT4',
            'GTE'                              => 'GTE',
            'LMP2', 'LMP 2'                    => 'LMP2',
            'CUP', 'PORSCHE CUP', 'GT3 CUP'   => 'CUP',
            'ST', 'SUPER TROFEO'               => 'ST',
            'CHL', 'CHALLENGE'                 => 'CHL',
            'TCX', 'TCR'                       => 'TCX',
            default                            => 'FreeForAll',
        };
    }
}