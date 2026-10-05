<?php

// The XCL Supporter membership, paid through Mollie (App\Services\MembershipService): one
// plan with every perk, billed monthly or yearly. The perks are unlocked wherever the code
// checks User::isSupporter(); the list below is only what the Memberships page shows.
// 'soon' marks a perk that isn't built yet.
return [
    // Launch switch. Off: the Memberships page shows the plan with "Launching soon" for
    // everyone except staff, who can run a checkout to test it. On: anyone can sign up.
    // Going live = a live_ MOLLIE_KEY + MEMBERSHIP_CHECKOUT_ENABLED=true.
    'checkout_enabled' => (bool) env('MEMBERSHIP_CHECKOUT_ENABLED', false),

    'name' => 'XCL Supporter',
    'currency' => 'EUR',

    // How a supporter can pay — memberships.plan holds the chosen key. Intervals in Mollie's
    // format ("1 month", "12 months", "1 day"). Set MEMBERSHIP_INTERVAL="1 day" in a test
    // environment to see a monthly renewal the next day instead of a month later.
    'billing' => [
        'monthly' => ['label' => 'Monthly', 'price' => '2.99', 'per' => 'month', 'interval' => env('MEMBERSHIP_INTERVAL', '1 month')],
        'yearly' => ['label' => 'Yearly', 'price' => '29.99', 'per' => 'year', 'interval' => env('MEMBERSHIP_YEARLY_INTERVAL', '12 months')],
    ],

    // Drivers in a My Team (owner included). A supporter's team has no limit.
    'base_team_seats' => 6,

    'perks' => [
        ['fa-solid fa-star', 'Supporter badge', 'A ★ next to your name on the drivers list and leaderboards.'],
        ['fa-solid fa-chart-line', 'Detailed race stats', 'Every lap time, sector splits, consistency and penalties on the results page.'],
        ['fa-solid fa-video', 'Stream on event pages', 'Put your Twitch or YouTube stream in the Drivers Streaming bar of the races you enter.'],
        ['fa-solid fa-quote-left', 'Team / Quote in-game', 'Your own line of text under your name on every XCL server.'],
        ['fa-solid fa-id-badge', 'Your own in-game abbreviation', 'Pick the three letters next to your name on the leaderboard (standard: XCL).'],
        ['fa-solid fa-users', 'My Team: unlimited drivers', 'No seat limit on your team (6 without a membership).'],
        ['fa-brands fa-discord', 'XCL Supporter role in Discord', 'Including access to the supporters-only channel.'],
        ['fa-solid fa-headset', 'Priority support', 'Your questions and reports are picked up first.'],
        ['fa-solid fa-magnifying-glass-chart', 'Personal lap analysis', 'A breakdown of your own laps: where you gain and lose time.', 'soon' => true],
        ['fa-solid fa-user-group', 'Rivals', 'Follow the drivers you race against and compare yourself with them.', 'soon' => true],
        ['fa-solid fa-sliders', 'Setup help & setups', 'Help with your car setup, and setups for XCL races.', 'soon' => true],
        ['fa-solid fa-tower-broadcast', 'Live race timing & engineer', 'Follow the race live with timing and engineer info.', 'soon' => true],
        ['fa-solid fa-square-poll-vertical', 'Event voting', 'Vote on tracks, cars and formats for upcoming events.', 'soon' => true],
        ['fa-solid fa-trophy', 'Host championships on XCL', 'Run your own championship on the XCL platform.', 'soon' => true],
        ['fa-solid fa-couch', 'VIP seats on fully booked events', 'A guaranteed spot when an event is full.', 'soon' => true],
        ['fa-solid fa-bolt', 'Early sign-up for special events', 'Get in before registration opens to everyone.', 'soon' => true],
    ],
];
