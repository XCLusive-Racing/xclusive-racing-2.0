<?php

// The XCLusive staff on the public Staff page (/team/staff). Each person is listed once,
// with every function they hold; the page also has a tab per function.
//
// Per person, add later as they come in:
//   'photo'   => '/images/staff/name.png' (null shows the blank card)
//   'flag'    => 'nl' (shows /images/flags/flag-nl.png)
//   'socials' => [['type' => 'instagram', 'href' => 'https://...'], ...]
//                (types: twitter, instagram, website, linkedin, facebook, twitch, tiktok, youtube)
return [

    // Function key => label, in the order the tabs are shown. A function without anyone
    // in it yet gets no tab.
    'roles' => [
        'admin' => 'Admin',
        'developer' => 'Website Developer',
        'event_manager' => 'Event Manager',
        'steward' => 'Steward',
        'discord_admin' => 'Discord Admin',
        'community_manager' => 'Community Manager',
    ],

    'members' => [
        ['name' => 'Chase Jones', 'roles' => ['admin', 'discord_admin'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Marco Schurink', 'roles' => ['admin', 'steward'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Sean Phillips', 'roles' => ['admin', 'discord_admin'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Olle Kuiper', 'roles' => ['admin', 'developer'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Justice', 'roles' => ['admin'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Jan Hartog', 'roles' => ['developer'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Alex Fear', 'roles' => ['event_manager', 'steward'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Dan Houdini', 'roles' => ['event_manager', 'steward'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Dorian Castelli', 'roles' => ['steward'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Gareth Andersson', 'roles' => ['steward'], 'photo' => null, 'flag' => null, 'socials' => []],
        ['name' => 'Bram Reinders', 'roles' => ['steward'], 'photo' => null, 'flag' => null, 'socials' => []],
    ],

];
