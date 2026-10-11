<?php

return [
    'name' => env('XCL_NAME', 'XCLusive Racing'),
    'tagline' => env('XCL_TAGLINE', 'The Lion is Born to Dominate'),
    'discord_url' => env('XCL_DISCORD_URL', 'https://discord.gg/3q5QAs8Dkw'),
    'sponsor' => env('XCL_SPONSOR', null),

    // The business behind the site, as shown on the Contact, Terms, Refund and Privacy
    // pages (payment providers and consumer law require these to be published). A line
    // that isn't set is left out of the pages.
    'legal' => [
        'company' => env('XCL_LEGAL_COMPANY', 'XCLusive Gaming Events'),
        'address' => env('XCL_LEGAL_ADDRESS'),       // street + number, postcode + city, country
        'coc' => env('XCL_LEGAL_COC'),           // Chamber of Commerce (KvK) number
        'vat' => env('XCL_LEGAL_VAT'),           // VAT (btw) number
        'email' => env('XCL_LEGAL_EMAIL', 'info@xclusiveracing.com'),
        'updated' => '2026-10-07',                   // "Last updated" date of the legal pages
    ],
];
