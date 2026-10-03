<?php

// XCL membership plans, paid monthly through Mollie (App\Services\MembershipService).
// Each plan includes everything of the plans below it (User::hasTier()). The perks are
// unlocked wherever the code checks hasTier(); the lists below are only what the
// Memberships page shows. 'soon' marks a perk that isn't built yet.
return [
    // Launch switch. Off: the Memberships page shows the plans with "Launching soon" for
    // everyone except staff, who can run a checkout to test it. On: anyone can sign up.
    // Going live = a live_ MOLLIE_KEY + MEMBERSHIP_CHECKOUT_ENABLED=true.
    'checkout_enabled' => (bool) env('MEMBERSHIP_CHECKOUT_ENABLED', false),

    'currency' => 'EUR',

    // Mollie interval format ("1 month", "14 days", "1 day"). Set MEMBERSHIP_INTERVAL="1 day"
    // in a test environment to see a recurring payment the next day instead of a month later.
    'interval' => env('MEMBERSHIP_INTERVAL', '1 month'),

    // Drivers in a My Team (owner included) when the owner has no plan.
    'base_team_seats' => 6,

    // Lowest to highest — the order is the tier order.
    'plans' => [
        'supporter' => [
            'name' => 'XCL Supporter',
            'price' => '2.95',
            'team_seats' => 8,
            'perks' => [
                ['fa-solid fa-star', 'Supporter badge', 'A ★ next to your name on the drivers list and leaderboards.'],
                ['fa-solid fa-quote-left', 'Team / Quote in-game', 'Your own line of text under your name on every XCL server.'],
                ['fa-brands fa-discord', 'XCL Supporter role in Discord', 'Including access to the supporters-only channel.'],
                ['fa-solid fa-users', 'My Team: 8 drivers', '2 extra seats on top of the standard 6.'],
                ['fa-solid fa-headset', 'Priority support', 'Your questions and reports are picked up first.'],
            ],
        ],
        'member' => [
            'name' => 'XCLusive Member',
            'price' => '4.95',
            'team_seats' => 12,
            'perks' => [
                ['fa-brands fa-discord', 'XCLusive Member role in Discord', 'A special role on top of the Supporter role.'],
                ['fa-solid fa-video', 'Stream on event pages', 'Put your Twitch or YouTube stream in the Drivers Streaming bar of the races you enter.'],
                ['fa-solid fa-users', 'My Team: 12 drivers', '6 extra seats on top of the standard 6.'],
                ['fa-solid fa-chart-line', 'Extra stats', 'Deeper insight into your race and lap data.', 'soon' => true],
                ['fa-solid fa-sliders', '5 free setups per month', 'From the XCL setup shop.', 'soon' => true],
            ],
        ],
        'vip' => [
            'name' => 'XCLusive VIP',
            // Not for sale until a price is set (MEMBERSHIP_VIP_PRICE, e.g. "9.95").
            'price' => env('MEMBERSHIP_VIP_PRICE'),
            'team_seats' => null, // unlimited
            'perks' => [
                ['fa-brands fa-discord', 'XCLusive VIP role in Discord', 'On top of the Supporter and Member roles.'],
                ['fa-solid fa-infinity', 'My Team: unlimited drivers', 'No seat limit on your team.'],
                ['fa-solid fa-trophy', 'Host championships on XCL', 'Run your own championship on the XCL platform.', 'soon' => true],
                ['fa-solid fa-couch', 'VIP seats on fully booked events', 'A guaranteed spot when an event is full.', 'soon' => true],
                ['fa-solid fa-bolt', 'Early sign-up for special events', 'Get in before registration opens to everyone.', 'soon' => true],
            ],
        ],
    ],
];
