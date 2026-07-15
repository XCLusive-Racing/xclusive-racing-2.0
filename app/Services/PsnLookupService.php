<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PsnLookupService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_SECONDS = 1;

    public function lookup(string $username): array
    {
        $lastException = null;

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                sleep(self::RETRY_DELAY_SECONDS);
            }

            try {
                $client = Http::timeout(15);
                if (app()->environment('local')) {
                    $client = $client->withoutVerifying();
                }
                $response = $client->get('https://psnlookupxcl.netlify.app/api/psn', [
                    'username' => $username,
                    'key'      => config('services.psn_lookup.api_key'),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (empty($data['onlineId']) || empty($data['accountId'])) {
                        throw new RuntimeException("PSN account '{$username}' not found.");
                    }

                    return [
                        'onlineId'  => $data['onlineId'],
                        'accountId' => $data['accountId'],
                    ];
                }

                $lastException = new RuntimeException(
                    "PSN lookup returned HTTP {$response->status()} for '{$username}'."
                );
            } catch (ConnectionException $e) {
                $lastException = new RuntimeException("PSN lookup connection failed: {$e->getMessage()}");
            }
        }

        throw $lastException ?? new RuntimeException("PSN lookup failed for '{$username}'.");
    }
}