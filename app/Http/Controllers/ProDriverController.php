<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Result;

class ProDriverController extends Controller
{
    public static function allDrivers(): array
    {
        $drivers = [
            'dirk-schouten' => [
                'name' => 'Dirk Schouten',
                'flag' => 'netherlands',
                'nationality' => 'Dutch',
                'portrait' => '/images/drivers/D.Schouten.png',
                'hero_category' => 'Porsche_Super_Cup_Banner',
                'profile_category' => 'dirk-profile-page',
                'bio' => 'Dirk Schouten is a Dutch professional racing driver representing XCLusive Racing on the international motorsport stage. Competing in some of Europe\'s most prestigious single-make championships, Dirk has proven his racecraft with consistent podium finishes and a maiden class victory at Monaco. Known for his commitment to improvement and engaging presence both on and off track, he is a cornerstone of the XCLusive Racing professional programme.',
                'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/dirk_schouten_/'],
                    ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@dirkschouten34'],
                    ['type' => 'youtube',   'href' => 'https://www.youtube.com/channel/UC6PwvyoGGVmql0a2Ch5RJ9w'],
                    ['type' => 'linkedin',  'href' => 'https://www.linkedin.com/in/dirk-schouten-690221167/'],
                    ['type' => 'facebook',  'href' => 'https://www.facebook.com/p/Dirk-Schouten-100007931509430/'],
                ],
                'followers' => [
                    'headline' => 'I deliver valuable exposure and awareness for my sponsors.',
                    'stats' => [
                        ['type' => 'instagram', 'count' => '398.000+', 'label' => 'Instagram followers'],
                        ['type' => 'tiktok',    'count' => '230.000+', 'label' => 'TikTok followers'],
                        ['type' => 'youtube',   'count' => '425.000+', 'label' => 'YouTube subscribers'],
                    ],
                ],
            ],

            'mats-van-rooijen' => [
                'name' => 'Mats van Rooijen',
                'flag' => 'netherlands',
                'nationality' => 'Dutch',
                'portrait' => '/images/drivers/M.vanRooijen.png',
                'bio' => 'Placeholder bio — to be delivered by the driver.',
                'socials' => [
                    ['type' => 'website',   'href' => 'https://matsvrooijen.vercel.app/'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/matsvanrooijen_official/'],
                    ['type' => 'linkedin',  'href' => 'https://www.linkedin.com/in/mats-van-rooijen-540354314/'],
                ],
            ],
        ];

        return $drivers;
    }

    public function index()
    {
        $drivers = self::allDrivers();

        return view('teams.pro.index', compact('drivers'));
    }

    public function show(string $slug)
    {
        $all = self::allDrivers();
        abort_unless(isset($all[$slug]), 404);

        $driver = $all[$slug];
        $driver['slug'] = $slug;

        // Results live in the database, reshaped back into the exact array shape
        // teams/pro/show.blade.php expects. Only looked up here (not in allDrivers())
        // so the homepage/index, which only need bio data or a count, never touch
        // the results table.
        $driver['results'] = Result::legacyProArrayForSubject($slug);

        // Hero/banner image, tried in order:
        //  1. an explicit hero_category override (e.g. a sponsor-branded banner)
        //  2. a media library upload named "<drivername>_banner" (any separator/case —
        //     matched by stripping non-alphanumerics, so "Dirk Schouten_Banner.png",
        //     "dirkschouten-banner" etc. all match)
        //  3. the legacy driver-<slug> category, kept for backwards compatibility
        $driver['hero'] = $this->findHeroByCategory($driver['hero_category'] ?? null)
            ?? $this->findHeroByDriverName($driver['name'])
            ?? $this->findHeroByCategory('driver-'.$slug);

        // Profile photo (shown beside upcoming races)
        $driver['profile_image'] = null;
        if (! empty($driver['profile_category'])) {
            $profile = Media::where('category', $driver['profile_category'])
                ->where('type', 'image')
                ->latest()
                ->first();
            $driver['profile_image'] = $profile?->url;
        }

        return view('teams.pro.show', compact('driver'));
    }

    private function findHeroByCategory(?string $category): ?string
    {
        if (! $category) {
            return null;
        }

        return Media::where('category', $category)
            ->where('type', 'image')
            ->latest()
            ->first()?->url;
    }

    // Matches a media title/filename like "<drivername>_banner" against the driver's
    // name, ignoring case and any separators (spaces, underscores, hyphens) on both
    // sides — so "Dirk Schouten Banner.png", "dirk_schouten-banner" etc. all match.
    private function findHeroByDriverName(string $name): ?string
    {
        $needle = self::normalizeForMatch($name).'banner';

        $candidates = Media::where('type', 'image')
            ->where(function ($q) {
                $q->where('title', 'like', '%banner%')
                    ->orWhere('original_name', 'like', '%banner%');
            })
            ->latest()
            ->get(['id', 'title', 'original_name', 'path', 'type', 'youtube_id']);

        foreach ($candidates as $media) {
            $label = $media->title ?: $media->original_name;
            if (self::normalizeForMatch($label) === $needle) {
                return $media->url;
            }
        }

        return null;
    }

    private static function normalizeForMatch(string $value): string
    {
        $value = preg_replace('/\.[a-z0-9]{2,5}$/i', '', $value); // strip file extension

        return strtolower(preg_replace('/[^a-z0-9]/i', '', $value));
    }
}
