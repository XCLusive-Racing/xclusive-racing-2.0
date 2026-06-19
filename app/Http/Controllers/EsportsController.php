<?php

namespace App\Http\Controllers;

class EsportsController extends Controller
{
    public function index()
    {
        $drivers = [
            'lmu' => [
                ['name' => 'Giuseppe Dinoia',   'flag' => 'italy',          'photo' => null],
                ['name' => 'Denis Ebert',        'flag' => 'germany',        'photo' => null],
                ['name' => 'Wilson Gigé',        'flag' => 'france',         'photo' => '/images/drivers/W.Gige.png'],
                ['name' => 'Luca Gönnheimer',    'flag' => 'germany',        'photo' => null],
                ['name' => 'Kyan Heyninck',      'flag' => 'belgium',        'photo' => null],
                ['name' => 'Alex Lucky',         'flag' => 'italy',          'photo' => '/images/drivers/A.Lucky.png'],
                ['name' => 'Paul Möller',        'flag' => 'germany',        'photo' => null],
                ['name' => 'Thato Motubatse',    'flag' => 'south%20africa', 'photo' => null],
                ['name' => 'Lukas Oesterreich',  'flag' => 'germany',        'photo' => null],
                ['name' => 'Gianluca Walczak',   'flag' => 'germany',        'photo' => null],
                ['name' => 'Kyle Williams',      'flag' => 'south%20africa', 'photo' => null],
                ['name' => 'Aidan Winchester',   'flag' => 'united%20kingdom','photo' => null],
            ],
            'acc' => [
                ['name' => 'Nat Benett',         'flag' => 'united%20kingdom','photo' => null],
                ['name' => 'Joakim Eriksson',    'flag' => null,             'photo' => null],
                ['name' => 'Fabio Faar',         'flag' => 'italy',          'photo' => null],
                ['name' => 'James Farish',       'flag' => 'united%20kingdom','photo' => '/images/drivers/J.Farish.png'],
                ['name' => 'Will Friedmann',     'flag' => 'france',         'photo' => null],
                ['name' => 'José García',        'flag' => null,             'photo' => null],
                ['name' => 'Sergio Hernández',   'flag' => null,             'photo' => null],
                ['name' => 'Matteo Mastromauro', 'flag' => 'italy',          'photo' => null],
                ['name' => 'Danny Meeldijk',     'flag' => 'netherlands',    'photo' => null],
                ['name' => 'Elmārs Miķelsons',   'flag' => 'latvia',         'photo' => null],
                ['name' => 'Florian Ochsmann',   'flag' => 'germany',        'photo' => null],
                ['name' => 'Menno Peters',       'flag' => 'netherlands',    'photo' => null],
                ['name' => 'Phil Sourcy',        'flag' => null,             'photo' => null],
                ['name' => 'Gianluca Zambione',  'flag' => 'italy',          'photo' => null],
                ['name' => 'Federico Zamblera',  'flag' => 'italy',          'photo' => null],
            ],
            'iracing' => [
                ['name' => 'Ethan Amburg',      'flag' => 'usa',     'photo' => null],
                ['name' => 'James Curtin',      'flag' => 'usa',     'photo' => null],
                ['name' => 'CJ Farish',         'flag' => 'usa',     'photo' => null],
                ['name' => 'Mario García',      'flag' => null,      'photo' => null],
                ['name' => 'Jake Goldman',      'flag' => 'usa',     'photo' => null],
                ['name' => 'Michael Martinz',   'flag' => 'austria', 'photo' => null],
                ['name' => 'Parker Soukup',     'flag' => 'usa',     'photo' => '/images/drivers/P.Soukup.png'],
                ['name' => 'André Damrat',      'flag' => 'germany', 'photo' => null],
            ],
        ];

        return view('teams.esports.index', compact('drivers'));
    }
}
