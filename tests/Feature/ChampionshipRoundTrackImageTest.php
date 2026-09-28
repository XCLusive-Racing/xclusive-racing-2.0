<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: championship rounds use their track's stock image as banner,
// like regular events do, with the championship badge (else the league logo) on top.
class ChampionshipRoundTrackImageTest extends TestCase
{
    use RefreshDatabase;

    private function media(string $file): void
    {
        Media::create([
            'filename' => $file, 'original_name' => $file, 'path' => 'tracks/'.$file, 'mime_type' => 'image/png',
        ]);
    }

    private function race(string $track, array $attributes = []): Race
    {
        return new Race(array_merge(['track' => $track, 'championship_id' => 7], $attributes));
    }

    public function test_a_round_without_an_image_shows_its_tracks_image(): void
    {
        foreach (['Spa.png', 'Nurburgring.png', 'Nords.png'] as $file) {
            $this->media($file);
        }

        $this->assertStringEndsWith('tracks/Spa.png', $this->race('Spa-Francorchamps')->image_url);
        $this->assertStringEndsWith('tracks/Spa.png', $this->race('spa')->image_url);
        $this->assertStringEndsWith('tracks/Nurburgring.png', $this->race('Nurburgring')->image_url);
        $this->assertStringEndsWith('tracks/Nords.png', $this->race('Nürburgring Nordschleife')->image_url);
    }

    public function test_an_own_image_wins_and_unknown_tracks_or_regular_events_get_none(): void
    {
        $this->media('Spa.png');

        $this->assertStringEndsWith('own/banner.png', $this->race('Spa', ['image' => 'own/banner.png'])->image_url);
        $this->assertNull($this->race('Some Street Circuit')->image_url);
        // A regular event's image is stored at creation — no fallback at display time.
        $this->assertNull($this->race('Spa', ['championship_id' => null])->image_url);
    }
}
