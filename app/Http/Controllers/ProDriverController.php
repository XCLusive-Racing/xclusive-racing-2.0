<?php

namespace App\Http\Controllers;

use App\Models\Media;

class ProDriverController extends Controller
{
    private function allDrivers(): array
    {
        return [
            'dirk-schouten' => [
                'name'             => 'Dirk Schouten',
                'flag'             => 'netherlands',
                'nationality'      => 'Dutch',
                'portrait'         => '/images/drivers/D.Schouten.png',
                'hero_category'    => 'Porsche_Super_Cup_Banner',
                'profile_category' => 'dirk-profile-page',
                'bio'         => 'Dirk Schouten is a Dutch professional racing driver representing XCLusive Racing on the international motorsport stage. Competing in some of Europe\'s most prestigious single-make championships, Dirk has proven his racecraft with consistent podium finishes and a maiden class victory at Monaco. Known for his commitment to improvement and engaging presence both on and off track, he is a cornerstone of the XCLusive Racing professional programme.',
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
                'results' => [
                    2026 => [],
                    2025 => [
                        [
                            'championship' => 'Porsche Mobil 1 Supercup',
                            'races' => [
                                ['track' => 'Imola',             'class' => 'Rookie', 'positions' => ['P4']],
                                ['track' => 'Monaco',            'class' => 'Rookie', 'positions' => ['P1']],
                                ['track' => 'Barcelona',         'class' => 'Rookie', 'positions' => ['P3']],
                                ['track' => 'Red Bull Ring',     'class' => 'Rookie', 'positions' => ['P3']],
                                ['track' => 'Hungaroring',       'class' => 'Rookie', 'positions' => ['P2']],
                                ['track' => 'Spa-Francorchamps', 'class' => 'Rookie', 'positions' => ['P3']],
                                ['track' => 'Zandvoort',         'class' => 'Rookie', 'positions' => ['P3']],
                                ['track' => 'Monza',             'class' => 'Rookie', 'positions' => ['P3']],
                            ],
                            'standing' => 'P3 Rookie · P15 Overall',
                        ],
                        [
                            'championship' => 'Porsche Carrera Cup Italia',
                            'races' => [
                                ['track' => 'Misano',     'positions' => ['P15', 'P9']],
                                ['track' => 'Vallelunga', 'positions' => ['P6',  'P6']],
                                ['track' => 'Mugello',    'positions' => ['P11', 'P15']],
                                ['track' => 'Imola',      'positions' => ['P8',  'P25']],
                                ['track' => 'Misano',     'positions' => ['P9',  'P13']],
                                ['track' => 'Monza',      'positions' => ['P3',  'P3']],
                            ],
                            'standing' => 'P7 Overall Championship',
                        ],
                    ],
                    2024 => [
                        [
                            'championship' => 'Porsche Carrera Cup Benelux',
                            'races' => [
                                ['track' => 'Spa-Francorchamps', 'positions' => ['P10', 'P1']],
                                ['track' => 'Zandvoort',         'positions' => ['P1',  'P2']],
                                ['track' => 'Imola',             'positions' => ['P18', 'P5']],
                                ['track' => 'TT Assen',          'positions' => ['P2',  'P1']],
                                ['track' => 'Red Bull Ring',     'positions' => ['P4',  'P2']],
                                ['track' => 'Circuit Zolder',    'positions' => ['P5',  'P3']],
                            ],
                            'standing' => 'Champion',
                        ],
                        [
                            'championship' => 'Belcar',
                            'races' => [
                                ['track' => 'Zolder', 'positions' => ['P1']],
                            ],
                            'standing' => '',
                        ],
                    ],
                    2023 => [
                        [
                            'championship' => 'Porsche Carrera Cup Benelux',
                            'races' => [
                                ['track' => 'Spa-Francorchamps', 'positions' => ['P8',  'P10']],
                                ['track' => 'Hockenheim',        'positions' => ['P5',  'P7']],
                                ['track' => 'Zandvoort',         'positions' => ['P4',  'P6']],
                                ['track' => 'TT Assen',          'positions' => ['P8',  'P19']],
                                ['track' => 'Zolder',            'positions' => ['P2',  'P5']],
                                ['track' => 'Red Bull Ring',     'positions' => ['P6',  'P10']],
                            ],
                            'standing' => 'P2 Rookie · P4 Overall',
                        ],
                    ],
                    2022 => [
                        [
                            'championship' => 'GT Cup Open',
                            'races' => [
                                ['track' => 'Paul Ricard',  'positions' => ['P2', 'P3']],
                                ['track' => 'Spa',          'positions' => ['P2', 'P3']],
                                ['track' => 'Hungaroring',  'positions' => ['P2', 'P2']],
                                ['track' => 'Monza',        'positions' => ['P2']],
                                ['track' => 'Barcelona',    'positions' => ['P3']],
                            ],
                            'standing' => 'Vice-Champion · 4/5 Pole Positions',
                        ],
                    ],
                    2021 => [
                        [
                            'championship' => 'GT Cup Open',
                            'races' => [
                                ['track' => 'Spa',       'positions' => ['P2', 'P3']],
                                ['track' => 'Monza',     'positions' => ['P1', 'P3']],
                                ['track' => 'Barcelona', 'positions' => ['P2', 'P3']],
                            ],
                            'standing' => 'P3 Overall',
                        ],
                        [
                            'championship' => 'Supercarchallenge',
                            'races' => [
                                ['track' => 'Spa', 'positions' => ['P1', 'P1']],
                            ],
                            'standing' => '',
                        ],
                        [
                            'championship' => 'Belcar',
                            'races' => [
                                ['track' => 'Hockenheim', 'positions' => ['P3']],
                            ],
                            'standing' => '',
                        ],
                    ],
                ],
            ],

            'mats-van-rooijen' => [
                'name'        => 'Mats van Rooijen',
                'flag'        => 'netherlands',
                'nationality' => 'Dutch',
                'portrait'    => '/images/drivers/M.vanRooijen.png',
                'bio'         => 'Placeholder bio — to be delivered by the driver.',
                'socials' => [
                    ['type' => 'website',   'href' => 'https://matsvrooijen.vercel.app/'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/matsvanrooijen_official/'],
                    ['type' => 'linkedin',  'href' => 'https://www.linkedin.com/in/mats-van-rooijen-540354314/'],
                ],
                'results' => [
                    2025 => [],
                    2026 => [],
                ],
            ],

            'jesse-aalbregt' => [
                'name'        => 'Jesse Aalbregt',
                'flag'        => 'netherlands',
                'nationality' => 'Dutch',
                'portrait'    => '/images/drivers/J.Aalbregt.png',
                'bio'         => 'Placeholder bio — to be delivered by the driver.',
                'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/teamjesse81/'],
                    ['type' => 'youtube',   'href' => 'https://www.youtube.com/@teamjesse81'],
                    ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@teamjesse81'],
                    ['type' => 'twitch',    'href' => 'https://www.twitch.tv/teamjesse81'],
                ],
                'results' => [
                    2025 => [],
                    2026 => [],
                ],
            ],
        ];
    }

    public function index()
    {
        $drivers = $this->allDrivers();
        return view('teams.pro.index', compact('drivers'));
    }

    public function show(string $slug)
    {
        $all = $this->allDrivers();
        abort_unless(isset($all[$slug]), 404);

        $driver         = $all[$slug];
        $driver['slug'] = $slug;

        // Auto-pick the latest hero image uploaded via the media library
        $heroCategory   = $driver['hero_category'] ?? ('driver-' . $slug);
        $hero           = Media::where('category', $heroCategory)
            ->where('type', 'image')
            ->latest()
            ->first();
        $driver['hero'] = $hero?->url;

        // Profile photo (shown beside upcoming races)
        $driver['profile_image'] = null;
        if (!empty($driver['profile_category'])) {
            $profile = Media::where('category', $driver['profile_category'])
                ->where('type', 'image')
                ->latest()
                ->first();
            $driver['profile_image'] = $profile?->url;
        }

        return view('teams.pro.show', compact('driver'));
    }
}
