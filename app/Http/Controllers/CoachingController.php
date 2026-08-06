<?php

namespace App\Http\Controllers;

class CoachingController extends Controller
{
    public function index()
    {
        $coaches = [
            [
                'slug'         => 'nikodem',
                'name'         => 'Nikodem Wisniewski',
                'photo'        => '/images/coaches/nikodem.avif',
                'achievements' => [
                    'DriveLab Founder',
                    '24h Virtual Le Mans Winner',
                    'FIA Silver Racing Driver',
                    'Go Setups Creator',
                ],
                'games'     => ['lmu', 'acc'],
                'platforms' => ['pc'],
            ],
            [
                'slug'         => 'dominik',
                'name'         => 'Dominik Blajer',
                'photo'        => '/images/coaches/dominik.avif',
                'achievements' => [
                    'SRO IGTC Champion',
                    'Alpine Simracing Champion',
                    'Hyundai N Virtual Cup Champion',
                    'Hymo Setups Creator',
                ],
                'games'     => ['lmu', 'acc', 'iracing'],
                'platforms' => ['pc'],
            ],
            [
                'slug'         => 'przemek',
                'name'         => 'Przemysław Lemanek',
                'photo'        => '/images/coaches/przemek.avif',
                'achievements' => [
                    '2023 iRacing GP Series Champion',
                    '2nd Place 2024 iRacing Daytona 24h',
                    '9200 iRating',
                ],
                'games'     => ['iracing'],
                'platforms' => ['pc'],
                // Top-anchored like the other coaches, nudged down 50px so his
                // head lines up with Dominik's — crops the extra 50px off the
                // bottom instead of the top.
                'photo_position' => 'center 50px',
                'photo_zoom'     => 1,
            ],
            [
                'slug'         => 'dorian',
                'name'         => 'Dorian Castelli',
                'photo'        => '/images/coaches/dorian.png',
                // Placeholder bullets — no specific achievements were provided for
                // Dorian, unlike the other coaches; swap these for real ones.
                'achievements' => [
                    'ACC Console Specialist',
                    'PS5 & Xbox Racing Coach',
                ],
                'games'     => ['acc'],
                'platforms' => ['ps5', 'xbox'],
                // Source photo is a full-length shot on a transparent background —
                // zoom in hard from the top so the crop ends around the waist
                // instead of showing the joggers/shoes.
                'photo_position' => 'top center',
                'photo_zoom'     => 2.1,
                // Overrides the default tier prices below
                'pricing' => [
                    'pro'      => '€29.90',
                    'ultimate' => '€49.90',
                ],
            ],
        ];

        $packageTiers = [
            [
                'key'         => 'pro',
                'label'       => 'PRO SESSION',
                'price'       => '€39.90',
                'duration'    => '1 hour',
                'description' => 'A focused 1-hour coaching session. Review your driving data, get personalized feedback, and take your racecraft to the next level.',
            ],
            [
                'key'         => 'ultimate',
                'label'       => 'ULTIMATE SESSION',
                'price'       => '€69.90',
                'duration'    => '2 hours',
                'description' => 'An in-depth 2-hour session with full telemetry analysis, setup advice, and race strategy coaching. The complete coaching experience.',
            ],
        ];

        $setupCreators = [
            [
                'name'   => 'GO SETUPS',
                'by'     => 'Nikodem Wisniewski',
                'desc'   => 'Professional ACC and LMU setups used by top drivers.',
                'href'   => '#',
            ],
            [
                'name'   => 'HYMO SETUPS',
                'by'     => 'Dominik Blajer',
                'desc'   => 'Race-winning setups for ACC, LMU and iRacing.',
                'href'   => '#',
            ],
        ];

        return view('coaching.index', compact('coaches', 'packageTiers', 'setupCreators'));
    }
}
