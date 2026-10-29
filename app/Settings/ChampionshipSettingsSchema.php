<?php

namespace App\Settings;

use Illuminate\Support\Arr;

// The single source of truth for every championship rule that isn't a real,
// queried/filtered database column. Every rule costs a schema entry here, not a
// migration — the wizard form, its per-step validation, and the model's typed
// settings object are all generated from this one definition.
//
// Versioning: CURRENT_VERSION bumps whenever a field is added, renamed or
// retyped. upgrade() always fills whatever the current schema defines but a
// stored blob is missing, regardless of the blob's own settings_version — so a
// championship saved under an old version keeps loading (and gets top-up
// defaults for new keys) once this class gains fields for a new version.
class ChampionshipSettingsSchema
{
    const CURRENT_VERSION = 17;

    // A comma-separated list of whole kg values, each -40..40 (ACC's BOP range).
    const KG_LIST_RULE = 'regex:/^\s*-?(40|[1-3]?\d)(\s*,\s*-?(40|[1-3]?\d))*\s*$/';

    // 2026-10-27: Format before Sessions (who races decides the rest) and Rounds
    // last before Review, so a round is added once everything it inherits is set.
    const STEPS = [
        'basics' => 'Basics',
        'format' => 'Format',
        'sessions' => 'Sessions',
        'scoring' => 'Scoring',
        'requirements' => 'Requirements',
        'penalties' => 'Penalties & Balance',
        'rounds' => 'Rounds',
        'review' => 'Review',
    ];

    // Settings groups, in the order they appear on the Penalties & Balance step
    // (balance isn't a wizard step of its own — it's rendered as a second section
    // on that step, since the wizard has a fixed step list that doesn't name it).
    const GROUPS = ['schedule', 'format', 'sessions', 'scoring', 'requirements', 'penalties', 'balance'];

    // Default subsection label for a field with no 'section' override of its own
    // (below) — lets a wizard step split its fields under more than one heading,
    // matching the race wizard's "Event" / "Track & Conditions" grouping
    // (resources/views/admin/races/form.blade.php), instead of one undifferentiated
    // pile of fields per step. Every 'sessions' field carries its own 'section' tag
    // (Session Lengths/Weather/Rating & Pitstops/Practice Server) rather than
    // relying on this default — that step alone has too many fields for one
    // undivided heading (2026-09, user feedback: "1 kopje heel veel ... andere
    // bijna niks").
    const GROUP_SECTION_LABELS = [
        'format' => 'Format',
        'scoring' => 'Scoring',
        'requirements' => 'Entry Requirements',
        'penalties' => 'Stewarding & Penalties',
        'balance' => 'Success Ballast',
    ];

    // A field can carry 'depends_on' => '<boolean field key, same group>', or
    // '<enum field key>=<option>' to show only while that option is picked (see
    // fields() below) — _field.blade.php renders it inside a wrapper
    // wizard.blade.php's shared script actually shows/hides (not just greys)
    // based on that boolean's live checked state, un-hiding again the moment
    // it's re-checked, no page reload. 2026-09, user feedback: dependent
    // fields for an unchecked option should disappear, "en als hij dat niet
    // wil kan hij ze weer uitklikken en dan verdwijnen de velden ook."

    // Maps a wizard step slug to the settings group(s) it edits and validates.
    // "sessions" used to ride on the Basics step (a period where there was no
    // dedicated Sessions step) — split back out once it grew past 15 fields on
    // its own (event-maker option parity: multiplier/pitstops/practice server),
    // which had made Basics enormous next to a thin Penalties step.
    // Schedule moved from Basics to Rounds (2026-10-27): it only drives the
    // dates Add Round suggests.
    const STEP_GROUPS = [
        'basics' => [],
        'rounds' => ['schedule'],
        'sessions' => ['sessions'],
        'format' => ['format'],
        'scoring' => ['scoring'],
        'requirements' => ['requirements'],
        'penalties' => ['penalties', 'balance'],
    ];

    public static function fields(): array
    {
        return [
            // Labels and help stay one short line each (2026-10-27 wizard revamp);
            // 'info' holds the longer explanation, shown behind an ⓘ tooltip.

            // --- Schedule (rendered on the Rounds step) ---
            ['group' => 'schedule', 'key' => 'start_date', 'type' => 'date', 'nullable' => true, 'default' => null,
                'label' => 'First Round Date', 'help' => 'Later rounds follow the recurrence.', 'rule' => 'nullable|date'],
            ['group' => 'schedule', 'key' => 'recurrence', 'type' => 'enum', 'options' => ['none', 'daily', 'weekly', 'biweekly', 'monthly'], 'default' => 'weekly',
                'label' => 'Recurrence', 'help' => 'None leaves round dates blank.', 'rule' => 'required|in:none,daily,weekly,biweekly,monthly'],
            ['group' => 'schedule', 'key' => 'day_of_week', 'type' => 'enum',
                'options' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], 'nullable' => true, 'default' => null,
                'label' => 'Race Day', 'help' => ''],
            // Real-world clock time only; the in-game clock default is
            // sessions.ingame_time_of_day (split 2026-09).
            ['group' => 'schedule', 'key' => 'time_of_day', 'type' => 'time', 'default' => '14:00',
                'label' => 'Start Time (UK)', 'help' => 'On the hour or half hour.',
                // Array form, not a pipe-delimited string: the regex itself
                // contains a "|", which a string rule would wrongly split on.
                'rule' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):(00|30)$/']],

            // --- Format ---
            ['group' => 'format', 'key' => 'multiclass_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Multiclass', 'help' => 'Separate car classes, each with its own standings.'],
            ['group' => 'format', 'key' => 'classes', 'type' => 'list', 'default' => [],
                'label' => 'Classes', 'help' => 'Each class with an optional entry cap.'],
            ['group' => 'format', 'key' => 'max_entries', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Max Entries', 'help' => 'A team car counts as one.',
                'info' => 'Also the number of car slots on the server. With multiclass, each class can have its own cap on top.'],
            // '!' prefix inverts the dependency: shown while multiclass is OFF.
            ['group' => 'format', 'key' => 'car_class', 'type' => 'enum', 'options' => ['GT2', 'GT3', 'GT4', 'TCX', 'GTC'], 'nullable' => true, 'default' => null, 'depends_on' => '!multiclass_enabled',
                'label' => 'Car Class', 'help' => ''],
            ['group' => 'format', 'key' => 'spectator_slots', 'type' => 'integer', 'nullable' => true, 'default' => 0,
                'label' => 'Spectator Slots', 'help' => 'On top of the entry cap.'],
            // Key kept from when this only covered driver-swap teams; it now decides
            // round sign-up for solo drivers and teams alike (ChampionshipRoundEntryService).
            ['group' => 'format', 'key' => 'team_registration_scope', 'type' => 'enum', 'options' => ['per_round', 'championship'], 'default' => 'per_round',
                'label' => 'Sign-up', 'help' => 'Per round: also on each round\'s page.',
                'info' => 'Entries always go into every round and can skip one on its event page. Per round: signing up on a round page also enters the championship. Championship: only on the championship page.'],
            // v15: driver classes (Pro / Pro-Am / Am), independent of car classes.
            ['group' => 'format', 'key' => 'driver_classes_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Driver Classes',
                'label' => 'Driver Classes', 'help' => 'Pro, Pro-Am, Am and so on, each with its own standings.',
                'info' => 'Independent of the car. Put entries in a class on the Entries page, or give a class a rank range so drivers land in it when they register.'],
            ['group' => 'format', 'key' => 'driver_swaps_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Driver Swaps',
                'label' => 'Driver Swaps', 'help' => 'More than one driver per car.'],
            ['group' => 'format', 'key' => 'min_drivers_per_car', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Min Drivers per Car', 'help' => ''],
            ['group' => 'format', 'key' => 'max_drivers_per_car', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max Drivers per Car', 'help' => ''],
            // v10 (NLRL 2026-09): a team may enter several cars, each scoring on its own.
            ['group' => 'format', 'key' => 'max_cars_per_team', 'type' => 'integer', 'nullable' => true, 'default' => 1, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max Cars per Team', 'help' => 'Blank = 1. A driver is in one car only.', 'rule' => 'nullable|integer|min:1|max:20'],
            // Mirror the race wizard's in-game swap rules (AccServerConfigService::eventRules());
            // default here, still overridable per round.
            ['group' => 'format', 'key' => 'driver_stint_time_mins', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max Stint (min)', 'help' => 'Blank = no limit.', 'rule' => 'nullable|integer|min:1|max:1440'],
            ['group' => 'format', 'key' => 'max_total_driving_time_mins', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max Driving per Driver (min)', 'help' => 'Blank = no limit.', 'rule' => 'nullable|integer|min:1|max:1440'],
            ['group' => 'format', 'key' => 'mandatory_driver_swap', 'type' => 'boolean', 'default' => false, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Mandatory Driver Swap', 'help' => 'A driver change at the mandatory pitstop.'],

            // --- Sessions --- ('section' tags split the step into blocks; every
            // value here is the default for new rounds, overridable per round)
            ['group' => 'sessions', 'key' => 'race_length_minutes', 'type' => 'integer', 'default' => 30, 'section' => 'Session Lengths',
                'label' => 'Race (min)', 'help' => '', 'rule' => 'required|integer|min:1|max:999'],
            ['group' => 'sessions', 'key' => 'practice_enabled', 'type' => 'boolean', 'default' => true, 'section' => 'Session Lengths',
                'label' => 'Practice', 'help' => ''],
            ['group' => 'sessions', 'key' => 'practice_length_minutes', 'type' => 'integer', 'nullable' => true, 'default' => 15, 'section' => 'Session Lengths', 'depends_on' => 'practice_enabled',
                'label' => 'Practice (min)', 'help' => ''],
            ['group' => 'sessions', 'key' => 'qualifying_enabled', 'type' => 'boolean', 'default' => true, 'section' => 'Session Lengths',
                'label' => 'Qualifying', 'help' => ''],
            ['group' => 'sessions', 'key' => 'qualifying_length_minutes', 'type' => 'integer', 'nullable' => true, 'default' => 15, 'section' => 'Session Lengths', 'depends_on' => 'qualifying_enabled',
                'label' => 'Qualifying (min)', 'help' => ''],
            // Split from schedule.time_of_day (the real-world start time).
            ['group' => 'sessions', 'key' => 'ingame_time_of_day', 'type' => 'time', 'default' => '14:00', 'section' => 'Session Lengths',
                'label' => 'In-Game Time', 'help' => 'The clock in the game, on the hour.',
                'rule' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):00$/']],
            ['group' => 'sessions', 'key' => 'formation_lap_type', 'type' => 'enum', 'options' => ['short', 'full'], 'default' => 'full', 'section' => 'Session Lengths',
                'label' => 'Formation Lap', 'help' => '', 'rule' => 'required|in:short,full'],
            ['group' => 'sessions', 'key' => 'weather_mode', 'type' => 'enum', 'options' => ['fixed', 'randomised'], 'default' => 'fixed', 'section' => 'Weather',
                'label' => 'Weather', 'help' => 'Fixed uses the values next to it.', 'rule' => 'required|in:fixed,randomised'],
            ['group' => 'sessions', 'key' => 'ambient_temp', 'type' => 'integer', 'nullable' => true, 'default' => 20, 'section' => 'Weather',
                'label' => 'Air (°C)', 'help' => ''],
            ['group' => 'sessions', 'key' => 'track_temp', 'type' => 'integer', 'nullable' => true, 'default' => 26, 'section' => 'Weather',
                'label' => 'Track (°C)', 'help' => ''],
            ['group' => 'sessions', 'key' => 'cloud_level', 'type' => 'float', 'nullable' => true, 'default' => 0.2, 'section' => 'Weather',
                'label' => 'Clouds (0 to 1)', 'help' => ''],
            ['group' => 'sessions', 'key' => 'rain_level', 'type' => 'float', 'nullable' => true, 'default' => 0.0, 'section' => 'Weather',
                'label' => 'Rain (0 to 1)', 'help' => ''],
            // Without an explicit multiplier a round has no EventFormat to fall back on,
            // so RatingService would rate every length at a flat 1.0. Locked while
            // XCL Rating is off (_field.blade.php / round forms check xcl_rating_enabled).
            ['group' => 'sessions', 'key' => 'xcl_r_multiplier', 'type' => 'float', 'nullable' => true, 'default' => null, 'section' => 'Rating & Pitstops',
                'label' => 'XCL-R Multiplier', 'help' => '0.6 to 2.5. Blank = 1.0.', 'rule' => 'nullable|numeric|min:0.6|max:2.5',
                'info' => 'How much these races count for rating changes. Needs XCL Rating turned on (Basics).'],
            ['group' => 'sessions', 'key' => 'pitstop_count', 'type' => 'integer', 'nullable' => true, 'default' => 0, 'section' => 'Rating & Pitstops',
                'label' => 'Mandatory Pitstops', 'help' => '0 = none.', 'rule' => 'nullable|integer|min:0|max:9'],
            // On/off only (user-directed 2026-09): min_stop_secs is derived from it,
            // 25 when on, null when off (applyStepSettings() and the round actions).
            ['group' => 'sessions', 'key' => 'fixed_stop_time', 'type' => 'boolean', 'default' => false, 'section' => 'Rating & Pitstops',
                'label' => 'Fixed Stop Time (25s)', 'help' => 'Off = game default.'],
            // v16 (2026-10-26): its own on/off, separate from Fixed Stop Time.
            ['group' => 'sessions', 'key' => 'mandatory_refuelling', 'type' => 'boolean', 'default' => false, 'section' => 'Rating & Pitstops',
                'label' => 'Mandatory Refuelling', 'help' => 'Fuel must go in at the stop.'],
            // v17 (2026-10-27): next to Mandatory Refuelling, off by default.
            ['group' => 'sessions', 'key' => 'mandatory_tyre_change', 'type' => 'boolean', 'default' => false, 'section' => 'Rating & Pitstops',
                'label' => 'Mandatory Tyre Change', 'help' => 'Tyres must be changed at the stop.'],
            // Not rendered: the derived value of Fixed Stop Time, kept in the schema
            // for its default/upgrade path and Add/Edit Round's prefill.
            ['group' => 'sessions', 'key' => 'min_stop_secs', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Rating & Pitstops', 'hidden' => true,
                'label' => 'Minimum Stop Time (seconds)', 'help' => 'Derived from Fixed Stop Time: 25 when on, blank when off.', 'rule' => 'nullable|integer|min:1|max:3600'],
            // v8 (NLRL 2026-09): ACC's eventRules.json tyreSetCount.
            ['group' => 'sessions', 'key' => 'tyre_set_count', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Rating & Pitstops',
                'label' => 'Tyre Sets', 'help' => 'Dry sets per car. Blank = 50.', 'rule' => 'nullable|integer|min:1|max:50'],
            // v13 (2026-09): a 24h open practice session on the rounds' own server
            // (ChampionshipPracticeService / championships:push-practice).
            ['group' => 'sessions', 'key' => 'practice_server_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Practice Server',
                'label' => '24h Practice Server', 'help' => 'Open practice on the next round\'s track, every night from midnight (UK).',
                'info' => 'Runs on the next round\'s server, followed by that round\'s qualifying and race(s). Open to everyone. The race config takes over 5 minutes before each round. Takes effect when the server restarts.'],

            // --- Scoring ---
            ['group' => 'scoring', 'key' => 'points_scheme_id', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Points Scheme', 'help' => 'Pick a template or your own copy.'],
            ['group' => 'scoring', 'key' => 'drop_rounds', 'type' => 'integer', 'default' => 0,
                'label' => 'Drop Rounds', 'help' => 'Worst rounds left out of a driver\'s total.', 'rule' => 'required|integer|min:0|max:20'],
            // v11 (2026-09): finishing points scale against a 60-minute race.
            ['group' => 'scoring', 'key' => 'points_scale_with_length', 'type' => 'boolean', 'default' => false,
                'label' => 'Scale Points by Race Length', 'help' => '60 min = normal, 30 min = half, 90 min = 1.5×.',
                'info' => 'Bonus points (fastest lap, pole, leading a lap) are not scaled.'],
            ['group' => 'scoring', 'key' => 'team_points_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Team Standings',
                'label' => 'Team Standings', 'help' => 'Adds team standings next to the drivers.',
                'info' => 'Team cars: a table per car with all its drivers\' points. Solo drivers: a table per team, made of the drivers who race for it.'],
            // v12 (2026-09): only a team's best N cars score for the team each round.
            ['group' => 'scoring', 'key' => 'team_scoring_cars', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Team Standings', 'depends_on' => 'team_points_enabled',
                'label' => 'Scoring Cars per Team', 'help' => 'Best results per round. Blank = all.', 'rule' => 'nullable|integer|min:1|max:20',
                'info' => 'With team cars this adds a team championship on top of the per-car table.'],
            // Same rule as the legacy max_missed_rounds columns, applied in
            // Championship::buildDriverStandings().
            ['group' => 'scoring', 'key' => 'max_missed_rounds', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Missed Rounds',
                'label' => 'Rounds Allowed to Miss', 'help' => 'Blank = no limit.'],
            ['group' => 'scoring', 'key' => 'missed_rounds_action', 'type' => 'enum', 'options' => ['none', 'penalise'], 'default' => 'none', 'section' => 'Missed Rounds',
                'label' => 'Over the Limit', 'help' => ''],
            ['group' => 'scoring', 'key' => 'missed_rounds_penalty_points', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Missed Rounds',
                'label' => 'Penalty per Extra Round', 'help' => 'Points, when set to penalise.'],

            // --- Requirements ---
            ['group' => 'requirements', 'key' => 'min_xcl_rating_tier', 'type' => 'enum',
                'options' => ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'], 'nullable' => true, 'default' => null,
                'label' => 'Min. XCL Rating', 'help' => ''],
            ['group' => 'requirements', 'key' => 'max_xcl_rating_tier', 'type' => 'enum',
                'options' => ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'], 'nullable' => true, 'default' => null,
                'label' => 'Max. XCL Rating', 'help' => 'For a low-rating-only series.'],
            ['group' => 'requirements', 'key' => 'min_safety_rating', 'type' => 'float', 'nullable' => true, 'default' => null,
                'label' => 'Min. Safety Rating', 'help' => '0 to 10.', 'rule' => 'nullable|numeric|between:0,10'],
            // Locked like the League page's Discord toggle: the check is built
            // (ChampionshipController::discordMembershipFailure()), the bot isn't live yet.
            ['group' => 'requirements', 'key' => 'discord_membership_required', 'type' => 'boolean', 'default' => false, 'locked' => true,
                'label' => 'Discord Membership', 'help' => 'Entrants must be in the league\'s Discord.',
                'locked_help' => 'Temporarily locked: the Discord bot setup is not finished yet.'],
            ['group' => 'requirements', 'key' => 'manual_approval_required', 'type' => 'boolean', 'default' => false,
                'label' => 'Manual Approval', 'help' => 'Staff approve each entry before it counts.'],

            // --- Registration ---
            ['group' => 'requirements', 'key' => 'registration_mode', 'type' => 'enum', 'section' => 'Registration',
                'options' => ['always_open', 'closes_at_first_round', 'specific_period'], 'default' => 'always_open',
                'label' => 'Registration Closes', 'help' => 'Always open: stays open until you close it on Review.',
                'rule' => 'required|in:always_open,closes_at_first_round,specific_period'],
            ['group' => 'requirements', 'key' => 'registration_opens_at', 'type' => 'datetime', 'nullable' => true, 'default' => null, 'section' => 'Registration',
                'depends_on' => 'registration_mode=specific_period', 'label' => 'Opens At', 'help' => 'Blank = from publish.'],
            ['group' => 'requirements', 'key' => 'registration_closes_at', 'type' => 'datetime', 'nullable' => true, 'default' => null, 'section' => 'Registration',
                'depends_on' => 'registration_mode=specific_period', 'label' => 'Closes At', 'help' => 'Blank = no end date.'],
            ['group' => 'requirements', 'key' => 'waitlist_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Registration',
                'label' => 'Waiting List', 'help' => 'When full, new entries wait and move up automatically.'],

            // Shown publicly on the championship page as "Rules"; the one free-text
            // field for that (an earlier separate "Championship Rules" was merged in).
            ['group' => 'requirements', 'key' => 'notes', 'type' => 'text', 'nullable' => true, 'default' => null, 'section' => 'Public Info',
                'label' => 'Rules', 'help' => 'Shown on the championship page.'],
            ['group' => 'requirements', 'key' => 'prizes_text', 'type' => 'text', 'nullable' => true, 'default' => null, 'section' => 'Public Info',
                'label' => 'Prizes', 'help' => 'Shown on the championship page. Blank = hidden.'],

            // --- Penalties ---
            ['group' => 'penalties', 'key' => 'stewarding_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'XCL Stewarding', 'help' => 'Use XCL\'s reports and stewards.'],
            ['group' => 'penalties', 'key' => 'affects', 'type' => 'enum', 'options' => ['points', 'rating', 'both', 'none'], 'default' => 'none', 'depends_on' => 'stewarding_enabled',
                'label' => 'Penalties Affect', 'help' => '', 'rule' => 'required|in:points,rating,both,none'],
            ['group' => 'penalties', 'key' => 'post_race_time_penalties_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Post-Race Time Penalties', 'help' => 'Add time to a result after the race.'],

            // --- Balance (rendered on the Penalties & Balance step) ---
            // v9/v14 (NLRL, Sunday League 2026-09): success ballast, ranked per class
            // when multiclass (EntryBalanceService). Every kg value stays within ACC's
            // BOP range, -40..+40; below 0 is always allowed (user-directed).
            ['group' => 'balance', 'key' => 'success_ballast_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Success Ballast', 'help' => 'Weight by finishing position, on top of the BOP.'],
            ['group' => 'balance', 'key' => 'success_ballast_mode', 'type' => 'enum', 'options' => ['next_round', 'cumulative'], 'default' => 'next_round', 'depends_on' => 'success_ballast_enabled',
                'label' => 'Ballast Mode', 'help' => 'Next round, or built up over the season.',
                'info' => 'Next round: ballast only comes from the last round a driver raced. Cumulative: each round adds to (or takes from) the season total.'],
            ['group' => 'balance', 'key' => 'success_ballast_kg', 'type' => 'string', 'nullable' => true, 'default' => null, 'depends_on' => 'success_ballast_enabled',
                'label' => 'Ballast per Position (kg)', 'help' => 'P1 first, e.g. 5, 3, 2, 1, 0. The last value covers the rest.',
                'info' => 'Each value between -40 and 40; negative takes weight off. With more races in a round, the best result counts once. Ranked per class in multiclass.',
                'rule' => ['nullable', 'string', 'max:255', self::KG_LIST_RULE]],
            ['group' => 'balance', 'key' => 'success_ballast_cap', 'type' => 'integer', 'default' => 40, 'depends_on' => 'success_ballast_enabled',
                'label' => 'Max Ballast (kg)', 'help' => 'Up to 40, ACC\'s limit.',
                'rule' => ['sometimes', 'integer', 'min:0', 'max:40']],
            ['group' => 'balance', 'key' => 'success_ballast_min_laps', 'type' => 'integer', 'default' => 1, 'depends_on' => 'success_ballast_enabled',
                'label' => 'Minimum Laps', 'help' => 'Fewer laps keeps the current ballast.',
                'info' => 'For example an early DNF: no gain, no loss. A missed round never changes the ballast either.',
                'rule' => ['sometimes', 'integer', 'min:0', 'max:999']],
            ['group' => 'balance', 'key' => 'success_ballast_starting', 'type' => 'string', 'nullable' => true, 'default' => '2, 3, 4, 5, 6, 7, 8, 9, 10, 11', 'depends_on' => 'success_ballast_enabled',
                'label' => 'Starting Ballast per Round (kg)', 'help' => 'Cumulative only. Round 1 first, the last value covers the rest.',
                'info' => 'A driver starts with the value of the first round they race, so a mid-season joiner does not start light. Each value between -40 and 40. Empty = no starting ballast.',
                'rule' => ['nullable', 'string', 'max:255', self::KG_LIST_RULE]],
            ['group' => 'balance', 'key' => 'adjustments', 'type' => 'list', 'default' => [],
                'label' => 'Ballast & Restrictor Adjustments', 'help' => 'Per car or per driver overrides.'],
        ];
    }

    public static function fieldsForGroup(string $group): array
    {
        return array_values(array_filter(self::fields(), fn ($f) => $f['group'] === $group));
    }

    public static function fieldsForStep(string $step): array
    {
        $groups = self::STEP_GROUPS[$step] ?? [];

        return array_values(array_filter(self::fields(), fn ($f) => in_array($f['group'], $groups, true)));
    }

    // Groups a step's fields under a subsection label — a field's own 'section'
    // wins, otherwise its group's default (GROUP_SECTION_LABELS). Lets the wizard
    // render "Format" and "Driver Swaps" (say) as two visually separate blocks on
    // one step instead of every field in a flat, undifferentiated stack, matching
    // the race wizard's own grouped-subsection layout
    // (resources/views/admin/races/form.blade.php). Preserves fields()' own
    // definition order — PHP keeps insertion order for string array keys, and a
    // group's fields are already contiguous there.
    public static function sectionsForStep(string $step): array
    {
        $sections = [];
        foreach (self::fieldsForStep($step) as $field) {
            $label = $field['section'] ?? self::GROUP_SECTION_LABELS[$field['group']] ?? ucfirst($field['group']);
            $sections[$label][] = $field;
        }

        return $sections;
    }

    // The fully-populated, nested default settings array — every group, every key.
    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::fields() as $field) {
            Arr::set($defaults, $field['group'].'.'.$field['key'], $field['default']);
        }

        return $defaults;
    }

    // Fills any key the current schema defines but $stored is missing, at any
    // depth, without disturbing keys $stored already has — the upgrade path.
    public static function upgrade(array $stored): array
    {
        return array_replace_recursive(self::defaults(), $stored);
    }

    // Laravel validation rules for one or more groups, dot-keyed under $prefix so
    // they line up with the settings.{group}.{key} shape a request submits.
    // List-type fields are intentionally skipped — they're validated by hand in
    // the controller, since a repeatable structure doesn't reduce to one rule string.
    public static function rulesForGroups(array $groups, string $prefix = 'settings'): array
    {
        $rules = [];
        foreach (self::fields() as $field) {
            if (! in_array($field['group'], $groups, true) || $field['type'] === 'list') {
                continue;
            }

            $key = $prefix.'.'.$field['group'].'.'.$field['key'];
            $rules[$key] = $field['rule'] ?? self::inferredRule($field);
        }

        return $rules;
    }

    public static function rules(string $prefix = 'settings'): array
    {
        return self::rulesForGroups(self::GROUPS, $prefix);
    }

    // Plain-language rendering of one field's current value, for the Review step —
    // an organiser reads this back rather than interpreting raw form values.
    public static function humanValue(array $field, mixed $value): string
    {
        if ($field['type'] === 'list') {
            $count = is_array($value) ? count($value) : 0;

            return $count === 0 ? 'None set' : $count.' set';
        }

        if ($value === null || $value === '') {
            return 'Not set';
        }

        return match ($field['type']) {
            'boolean' => $value ? 'Yes' : 'No',
            'enum' => ucfirst(str_replace('_', ' ', (string) $value)),
            'time' => (string) $value,
            'float' => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
            default => (string) $value,
        };
    }

    private static function inferredRule(array $field): string
    {
        $nullable = ($field['nullable'] ?? false) ? 'nullable' : 'sometimes';

        return match ($field['type']) {
            'boolean' => 'boolean',
            'integer' => $nullable.'|integer',
            'float' => $nullable.'|numeric|between:0,1',
            'enum' => $nullable.'|in:'.implode(',', $field['options'] ?? []),
            'text' => $nullable.'|string|max:5000',
            'datetime' => $nullable.'|date',
            default => $nullable.'|string|max:255',
        };
    }
}
