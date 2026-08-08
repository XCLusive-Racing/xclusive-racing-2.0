<?php

namespace App\Http\Controllers;

class EsportsController extends Controller
{
    public function index()
    {
        $drivers = [
            'lmu' => [
                ['name' => 'Thomas Cauberghe',  'flag' => 'belgium',        'photo' => '/images/drivers/Cauberghe.png', 'socials' => [
                    ['type' => 'tiktok', 'href' => 'https://www.tiktok.com/@thomascauberghe?_r=1&_t=ZG-98h5kheJfzl'],
                    ['type' => 'twitch', 'href' => 'http://twitch.tv/thomascauberghee'],
                ]],
                ['name' => 'Giuseppe Dinoia',   'flag' => 'italy',          'photo' => '/images/drivers/Dinoia.png', 'socials' => []],
                ['name' => 'Denis Ebert',        'flag' => 'germany',        'photo' => null, 'socials' => []],
                ['name' => 'Lucas Britton',      'flag' => 'united%20kingdom', 'photo' => '/images/drivers/Britton.png', 'socials' => []],
                ['name' => 'Wilson Gigé',        'flag' => 'france',         'photo' => '/images/drivers/W.Gige.png', 'socials' => []],
                ['name' => 'Luca Gönnheimer',    'flag' => 'germany',        'photo' => '/images/drivers/goenni.png', 'socials' => []],
                ['name' => 'Kyan Heyninck',      'flag' => 'belgium',        'photo' => '/images/drivers/heyninck.png', 'socials' => []],
                ['name' => 'Alex Lucky',         'flag' => 'italy',          'photo' => '/images/drivers/A.Lucky.png', 'socials' => []],
                ['name' => 'Paul Möller',        'flag' => 'germany',        'photo' => null, 'socials' => []],
                ['name' => 'Thato Motubatse',    'flag' => 'south%20africa', 'photo' => null, 'socials' => []],
                ['name' => 'Lukas Oesterreich',  'flag' => 'germany',        'photo' => '/images/drivers/Louk.png', 'socials' => []],
                ['name' => 'Gianluca Walczak',   'flag' => 'germany',        'photo' => '/images/drivers/Walczak.png', 'socials' => []],
                ['name' => 'Kyle Williams',      'flag' => 'south%20africa', 'photo' => '/images/drivers/Williams.png', 'socials' => []],
                ['name' => 'Aidan Winchester',   'flag' => 'united%20kingdom','photo' => '/images/drivers/Winchester.png', 'socials' => []],
            ],
            'acc' => [
                ['name' => 'Nat Benett',         'flag' => 'united%20kingdom','photo' => '/images/drivers/Bennett.png', 'socials' => []],
                ['name' => 'Joakim Eriksson',    'flag' => null,             'photo' => '/images/drivers/Eriksson.png', 'socials' => []],
                ['name' => 'Fabio Faar',         'flag' => 'italy',          'photo' => null, 'socials' => []],
                ['name' => 'James Farish',       'flag' => 'united%20kingdom','photo' => '/images/drivers/J.Farish.png', 'socials' => []],
                ['name' => 'Will Friedmann',     'flag' => 'france',         'photo' => '/images/drivers/friedmann.png', 'socials' => []],
                ['name' => 'José García',        'flag' => null,             'photo' => '/images/drivers/Garcia.png', 'socials' => []],
                ['name' => 'Sergio Hernández',   'flag' => null,             'photo' => '/images/drivers/Hernández.png', 'socials' => []],
                ['name' => 'Matteo Mastromauro', 'flag' => 'italy',          'photo' => null, 'socials' => []],
                ['name' => 'Danny Meeldijk',     'flag' => 'netherlands',    'photo' => '/images/drivers/Danny.png', 'socials' => []],
                ['name' => 'Elmārs Miķelsons',   'flag' => 'latvia',         'photo' => '/images/drivers/elmars.png', 'socials' => []],
                ['name' => 'Florian Ochsmann',   'flag' => 'germany',        'photo' => '/images/drivers/ochsmann.png', 'socials' => []],
                ['name' => 'Menno Peters',       'flag' => 'netherlands',    'photo' => '/images/drivers/PetersM.png', 'socials' => []],
                ['name' => 'Phil Soucy',         'flag' => 'canada',         'photo' => null, 'socials' => []],
                ['name' => 'Gianluca Zambione',  'flag' => 'italy',          'photo' => '/images/drivers/Gianluca.png', 'socials' => []],
                ['name' => 'Federico Zamblera',  'flag' => 'italy',          'photo' => '/images/drivers/Zamby.png', 'socials' => []],
            ],
            'iracing' => [
                ['name' => 'Marcus Libz',       'flag' => 'canada',  'photo' => '/images/drivers/Libz.png', 'socials' => []],
                ['name' => 'James Curtin',      'flag' => 'usa',     'photo' => '/images/drivers/Curtin.png', 'socials' => []],
                ['name' => 'CJ Farish',         'flag' => 'usa',     'photo' => '/images/drivers/Mrshlk.png', 'socials' => []],
                ['name' => 'Mario García',      'flag' => null,      'photo' => '/images/drivers/Mare.png', 'socials' => []],
                ['name' => 'Jake Goldman',      'flag' => 'usa',     'photo' => '/images/drivers/Goldman2.png', 'socials' => []],
                ['name' => 'Michael Martinz',   'flag' => 'austria', 'photo' => null, 'socials' => []],
                ['name' => 'Parker Soukup',     'flag' => 'usa',     'photo' => '/images/drivers/P.Soukup.png', 'socials' => []],
                ['name' => 'André Damrat',      'flag' => 'germany', 'photo' => null, 'socials' => []],
            ],
        ];

        return view('teams.esports.index', compact('drivers'));
    }
}
