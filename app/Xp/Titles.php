<?php

namespace App\Xp;

use App\Models\Race;
use Illuminate\Support\Str;

// Maps what a race already records onto the XP system's title and platform.
// races.game is one of acc (ACC on console), ac (ACC on PC), lmu, iracing: so a race has
// always carried both facts, in one column. For XP, ACC is one title (games.key 'acc')
// raced on two platforms; every other title is PC.
class Titles
{
    public const CONSOLE = 'console';

    public const PC = 'pc';

    public static function game(?string $raceGame): string
    {
        return $raceGame === 'ac' ? 'acc' : (string) ($raceGame ?: 'acc');
    }

    public static function platform(?string $raceGame): string
    {
        return ($raceGame ?: 'acc') === 'acc' ? self::CONSOLE : self::PC;
    }

    // One key per circuit, so "Spa" and "Spa-Francorchamps" are the same track: the known
    // circuit whose name appears in the text (the longest, for "Nürburgring Nordschleife"),
    // else the text itself.
    public static function trackKey(?string $track): ?string
    {
        $name = self::normalize((string) $track);
        if ($name === '') {
            return null;
        }

        return once(function () use ($name) {
            $match = collect(array_keys(Race::TRACK_IMAGE_MAP))
                ->map(fn (string $known) => self::normalize($known))
                ->filter(fn (string $known) => $known === $name || preg_match('/\b'.preg_quote($known, '/').'\b/', $name))
                ->sortByDesc(fn (string $known) => strlen($known))
                ->first();

            return $match ?? $name;
        });
    }

    public static function isNordschleife(?string $track): bool
    {
        return self::trackKey($track) === 'nordschleife';
    }

    private static function normalize(string $text): string
    {
        return strtolower(trim(Str::ascii($text)));
    }
}
