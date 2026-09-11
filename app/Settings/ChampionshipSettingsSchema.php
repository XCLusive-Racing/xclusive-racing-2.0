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
    const CURRENT_VERSION = 7;

    const STEPS = [
        'basics'       => 'Basics',
        'sessions'     => 'Sessions',
        'rounds'       => 'Rounds',
        'format'       => 'Format',
        'scoring'      => 'Scoring',
        'requirements' => 'Requirements',
        'penalties'    => 'Penalties & Balance',
        'review'       => 'Review',
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
        'format'       => 'Format',
        'scoring'      => 'Scoring',
        'requirements' => 'Entry Requirements',
        'penalties'    => 'Stewarding & Penalties',
    ];

    // A field can carry 'depends_on' => '<boolean field key, same group>' (see
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
    const STEP_GROUPS = [
        'basics'       => ['schedule'],
        'sessions'     => ['sessions'],
        'format'       => ['format'],
        'scoring'      => ['scoring'],
        'requirements' => ['requirements'],
        'penalties'    => ['penalties', 'balance'],
    ];

    public static function fields(): array
    {
        return [
            // --- Schedule (rendered on the Basics step) ---
            ['group' => 'schedule', 'key' => 'start_date', 'type' => 'date', 'nullable' => true, 'default' => null,
                'label' => 'First Round Date', 'help' => 'Date of Round 1. Later rounds are suggested automatically from the recurrence below — still fully editable per round in Add Round.', 'rule' => 'nullable|date'],
            ['group' => 'schedule', 'key' => 'recurrence', 'type' => 'enum', 'options' => ['none', 'daily', 'weekly', 'biweekly', 'monthly'], 'default' => 'weekly',
                'label' => 'Recurrence', 'help' => 'How often rounds repeat. "None" leaves every round\'s date blank by default.', 'rule' => 'required|in:none,daily,weekly,biweekly,monthly'],
            ['group' => 'schedule', 'key' => 'day_of_week', 'type' => 'enum',
                'options' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], 'nullable' => true, 'default' => null,
                'label' => 'Race Day', 'help' => 'Only used when recurrence is weekly or bi-weekly.'],
            // Real-world clock time only — the separate in-game clock default lives
            // on the Sessions step (sessions.ingame_time_of_day) since it's a
            // session/weather-style concern, not a scheduling one. The two used to
            // be one shared field; split 2026-09 since a league can want a fixed
            // real-world slot (e.g. always 20:00 UK) with a different in-game time
            // (e.g. sunset lighting) or vice versa.
            ['group' => 'schedule', 'key' => 'time_of_day', 'type' => 'time', 'default' => '14:00',
                'label' => 'Real-World Start Time', 'help' => 'Suggests each round\'s real-world date & time — always on the hour. Still editable per round in Add Round.',
                // Array form, not a pipe-delimited string — the regex itself
                // contains a "|", which a string rule would wrongly split on.
                'rule' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):00$/']],

            // --- Format ---
            ['group' => 'format', 'key' => 'multiclass_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Multiclass', 'help' => 'Split entries into separate classes, each with their own standings.'],
            ['group' => 'format', 'key' => 'classes', 'type' => 'list', 'default' => [],
                'label' => 'Classes', 'help' => 'Each class has a name and a list of eligible cars. Only used when multiclass is on.'],
            ['group' => 'format', 'key' => 'max_entries', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Maximum Drivers/Teams', 'help' => 'Entry cap for the whole championship. Ignored when multiclass is on — set a cap per class instead.'],
            ['group' => 'format', 'key' => 'car_class', 'type' => 'enum', 'options' => ['GT2', 'GT3', 'GT4', 'TCX', 'GTC'], 'nullable' => true, 'default' => null,
                'label' => 'Car Class', 'help' => 'Same for every round of the championship. Ignored when multiclass is on — set a class per class below instead.'],
            ['group' => 'format', 'key' => 'spectator_slots', 'type' => 'integer', 'nullable' => true, 'default' => 0,
                'label' => 'Spectator Slots', 'help' => 'Extra slots reserved for spectators, on top of the entry cap.'],
            ['group' => 'format', 'key' => 'driver_swaps_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Driver Swaps',
                'label' => 'Driver Swaps', 'help' => 'Allow more than one driver to share a car during a round.'],
            ['group' => 'format', 'key' => 'min_drivers_per_car', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Minimum Drivers per Car', 'help' => 'Only used when driver swaps are on.'],
            ['group' => 'format', 'key' => 'max_drivers_per_car', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Maximum Drivers per Car', 'help' => 'Only used when driver swaps are on.'],
            ['group' => 'format', 'key' => 'team_registration_scope', 'type' => 'enum', 'options' => ['per_round', 'championship'], 'default' => 'per_round', 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Team Registration', 'help' => 'Only used when driver swaps are on. "Per round" (today\'s behaviour): a team still signs up separately for every round. "Whole championship": a team\'s car number, model and starting driver are captured once and copied into every round automatically — including rounds added later.'],
            // Mirror the race wizard's own in-game swap-enforcement fields
            // (admin/races/form.blade.php, AccServerConfigService::eventRules()) —
            // default here, still overridable per round in Add/Edit Round.
            ['group' => 'format', 'key' => 'driver_stint_time_mins', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max. Stint Time (minutes)', 'help' => 'Only used when driver swaps are on. Leave blank for no limit.', 'rule' => 'nullable|integer|min:1|max:1440'],
            ['group' => 'format', 'key' => 'max_total_driving_time_mins', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Max Driving Time / Driver (minutes)', 'help' => 'Only used when driver swaps are on. Leave blank for no limit.', 'rule' => 'nullable|integer|min:1|max:1440'],
            ['group' => 'format', 'key' => 'mandatory_driver_swap', 'type' => 'boolean', 'default' => false, 'section' => 'Driver Swaps', 'depends_on' => 'driver_swaps_enabled',
                'label' => 'Mandatory Pitstop Swap', 'help' => 'Only used when driver swaps are on. Requires a driver change at the mandatory pitstop.'],

            // --- Sessions --- (own wizard step, 'section' tags below split it into
            // Session Lengths / Weather / Rating & Pitstops / Practice Server rather
            // than one long undivided list)
            ['group' => 'sessions', 'key' => 'race_length_minutes', 'type' => 'integer', 'default' => 30, 'section' => 'Session Lengths',
                'label' => 'Race Length (minutes)', 'help' => 'Length of the race session.', 'rule' => 'required|integer|min:1|max:999'],
            ['group' => 'sessions', 'key' => 'practice_enabled', 'type' => 'boolean', 'default' => true, 'section' => 'Session Lengths',
                'label' => 'Practice Session', 'help' => 'Run a practice session before qualifying.'],
            ['group' => 'sessions', 'key' => 'practice_length_minutes', 'type' => 'integer', 'nullable' => true, 'default' => 15, 'section' => 'Session Lengths', 'depends_on' => 'practice_enabled',
                'label' => 'Practice Length (minutes)', 'help' => 'Only used when a practice session runs.'],
            ['group' => 'sessions', 'key' => 'qualifying_enabled', 'type' => 'boolean', 'default' => true, 'section' => 'Session Lengths',
                'label' => 'Qualifying Session', 'help' => 'Run a qualifying session before the race.'],
            ['group' => 'sessions', 'key' => 'qualifying_length_minutes', 'type' => 'integer', 'nullable' => true, 'default' => 15, 'section' => 'Session Lengths', 'depends_on' => 'qualifying_enabled',
                'label' => 'Qualifying Length (minutes)', 'help' => 'Only used when a qualifying session runs.'],
            // Split from schedule.time_of_day (the real-world start time) — see the
            // comment there. This is what prefills a round's own in-game clock in
            // Add/Edit Round, independent of the real-world slot.
            ['group' => 'sessions', 'key' => 'ingame_time_of_day', 'type' => 'time', 'default' => '14:00', 'section' => 'Session Lengths',
                'label' => 'In-Game Start Time', 'help' => 'The in-game clock time for every round — independent of the real-world start time on Basics.',
                'rule' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):00$/']],
            ['group' => 'sessions', 'key' => 'formation_lap_type', 'type' => 'enum', 'options' => ['short', 'full'], 'default' => 'full', 'section' => 'Session Lengths',
                'label' => 'Formation Lap', 'help' => 'How long the formation lap is.', 'rule' => 'required|in:short,full'],
            ['group' => 'sessions', 'key' => 'weather_mode', 'type' => 'enum', 'options' => ['fixed', 'randomised'], 'default' => 'fixed', 'section' => 'Weather',
                'label' => 'Weather', 'help' => 'Fixed weather is set once here; randomised is rolled per round.', 'rule' => 'required|in:fixed,randomised'],
            ['group' => 'sessions', 'key' => 'ambient_temp', 'type' => 'integer', 'nullable' => true, 'default' => 20, 'section' => 'Weather',
                'label' => 'Ambient Temperature (°C)', 'help' => 'Only used when weather is fixed.'],
            ['group' => 'sessions', 'key' => 'track_temp', 'type' => 'integer', 'nullable' => true, 'default' => 26, 'section' => 'Weather',
                'label' => 'Track Temperature (°C)', 'help' => 'Only used when weather is fixed.'],
            ['group' => 'sessions', 'key' => 'cloud_level', 'type' => 'float', 'nullable' => true, 'default' => 0.2, 'section' => 'Weather',
                'label' => 'Cloud Level (0–1)', 'help' => 'Only used when weather is fixed.'],
            ['group' => 'sessions', 'key' => 'rain_level', 'type' => 'float', 'nullable' => true, 'default' => 0.0, 'section' => 'Weather',
                'label' => 'Rain Level (0–1)', 'help' => 'Only used when weather is fixed.'],
            // Mirrors the race wizard's Custom Race "XCL-R Multiplier" field exactly
            // (admin/races/form.blade.php — that field is manual-only too, no
            // auto-derivation from length exists anywhere in the app to reuse).
            // Without this, a championship round has neither an EventFormat nor an
            // explicit multiplier, so RatingService::processRace() falls all the way
            // through to a flat 1.0 — every round rated the same regardless of length.
            // Only meaningful once XCL Rating is actually enabled for this
            // championship — rendered disabled otherwise (wizard.blade.php /
            // _round-shared-fields.blade.php / round-edit.blade.php all check
            // $championship->xcl_rating_enabled directly, this field has no
            // xcl_rating_enabled-awareness of its own).
            ['group' => 'sessions', 'key' => 'xcl_r_multiplier', 'type' => 'float', 'nullable' => true, 'default' => null, 'section' => 'Rating & Pitstops',
                'label' => 'XCL-R Multiplier', 'help' => 'How much this round\'s races count toward rating changes. Leave blank for the default (1.0, same for every length). Only used once XCL Rating is enabled.', 'rule' => 'nullable|numeric|min:0.1|max:10'],
            ['group' => 'sessions', 'key' => 'pitstop_count', 'type' => 'integer', 'nullable' => true, 'default' => 0, 'section' => 'Rating & Pitstops',
                'label' => 'Mandatory Pitstops', 'help' => 'Number of mandatory pitstops. Leave at 0 for none.', 'rule' => 'nullable|integer|min:0|max:9'],
            // Explicit dynamic/fixed choice rather than inferring "fixed" purely
            // from whether min_stop_secs happens to be filled in — AccServerConfigService
            // still only reads min_stop_secs (isRefuellingTimeFixed = !empty(...)), so
            // this boolean's only job is making sure that field is genuinely null
            // when "dynamic" is chosen, not just left at a stale prior value.
            ['group' => 'sessions', 'key' => 'fixed_stop_time', 'type' => 'boolean', 'default' => false, 'section' => 'Rating & Pitstops',
                'label' => 'Fixed Stop Time', 'help' => 'Off = dynamic (driver-controlled) stop time. On = a fixed minimum, set below.'],
            ['group' => 'sessions', 'key' => 'min_stop_secs', 'type' => 'integer', 'nullable' => true, 'default' => null, 'section' => 'Rating & Pitstops', 'depends_on' => 'fixed_stop_time',
                'label' => 'Minimum Stop Time (seconds)', 'help' => 'Only used when Fixed Stop Time is on.', 'rule' => 'nullable|integer|min:1|max:3600'],

            // --- Scoring ---
            ['group' => 'scoring', 'key' => 'points_scheme_id', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Points Scheme', 'help' => 'Pick a template or one you copied. League managers copy a template to create their own rather than editing a shared one.'],
            ['group' => 'scoring', 'key' => 'drop_rounds', 'type' => 'integer', 'default' => 0,
                'label' => 'Drop Rounds', 'help' => 'Number of a driver\'s lowest-scoring rounds excluded from their total.', 'rule' => 'required|integer|min:0|max:20'],
            // Fastest lap / pole / leading-a-lap bonus points live on the points
            // scheme itself now (PointsScheme::fastest_lap_points etc), not here —
            // a flat yes/no toggle couldn't carry an actual point value anyway.
            ['group' => 'scoring', 'key' => 'team_points_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Separate Team Points', 'help' => 'Score teams independently of individual drivers.'],

            // Mirrors the legacy native-championship form's "Rounds Allowed to
            // Miss" / "If limit exceeded" fields exactly
            // (resources/views/admin/championships/{create,edit}.blade.php) —
            // present as flat columns since before leagues existed, but never
            // actually wired into Championship::buildDriverStandings() for
            // either a native or a league championship until now.
            ['group' => 'scoring', 'key' => 'max_missed_rounds', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Rounds Allowed to Miss', 'help' => 'Leave blank for no limit.'],
            ['group' => 'scoring', 'key' => 'missed_rounds_action', 'type' => 'enum', 'options' => ['none', 'penalise'], 'default' => 'none',
                'label' => 'If Limit Exceeded', 'help' => 'What happens once a driver has missed more rounds than allowed.'],
            ['group' => 'scoring', 'key' => 'missed_rounds_penalty_points', 'type' => 'integer', 'nullable' => true, 'default' => null,
                'label' => 'Penalty Points per Extra Missed Round', 'help' => 'Only used when "If Limit Exceeded" is set to penalise. Deducted for every round missed beyond the allowed number.'],

            // --- Requirements ---
            ['group' => 'requirements', 'key' => 'min_xcl_rating_tier', 'type' => 'enum',
                'options' => ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'], 'nullable' => true, 'default' => null,
                'label' => 'Minimum XCL Rating', 'help' => 'Leave blank for no rating requirement.'],
            ['group' => 'requirements', 'key' => 'max_xcl_rating_tier', 'type' => 'enum',
                'options' => ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'], 'nullable' => true, 'default' => null,
                'label' => 'Maximum XCL Rating', 'help' => 'For a low-rating-only round — leave blank for no upper cap.'],
            ['group' => 'requirements', 'key' => 'min_safety_rating', 'type' => 'float', 'nullable' => true, 'default' => null,
                'label' => 'Minimum Safety Rating', 'help' => 'On the 0–10 scale. Leave blank for no requirement.', 'rule' => 'nullable|numeric|between:0,10'],
            ['group' => 'requirements', 'key' => 'discord_membership_required', 'type' => 'boolean', 'default' => false,
                'label' => 'Discord Membership Required', 'help' => 'Entrants must be a member of this league\'s Discord to register.'],
            ['group' => 'requirements', 'key' => 'manual_approval_required', 'type' => 'boolean', 'default' => false,
                'label' => 'Manual Approval of Entries', 'help' => 'Entries wait for league staff to approve before they count.'],
            // Shown publicly on the championship page (Phase 7) as "Rules" — this is
            // the one free-text field for that, not a second one. It used to be
            // labelled "Additional Requirements" with a separate "Championship
            // Rules" field added alongside it; the two were the same idea twice
            // (both "public free text the structured fields above don't cover"), so
            // they were merged back into this single field rather than shipping a
            // wizard step with two near-identical textareas.
            ['group' => 'requirements', 'key' => 'notes', 'type' => 'text', 'nullable' => true, 'default' => null,
                'label' => 'Rules & Additional Requirements', 'help' => 'Shown publicly on the championship page — anything the structured fields above don\'t cover.'],
            ['group' => 'requirements', 'key' => 'prizes_text', 'type' => 'text', 'nullable' => true, 'default' => null,
                'label' => 'Prizes', 'help' => 'Shown publicly on the championship page. Leave blank to show nothing.'],

            // --- Registration ---
            ['group' => 'requirements', 'key' => 'registration_mode', 'type' => 'enum', 'section' => 'Registration',
                'options' => ['always_open', 'closes_at_first_round', 'specific_period'], 'default' => 'always_open',
                'label' => 'Registration Closes', 'help' => 'Always open (close it manually), automatically at the first round\'s start time, or during a specific period.',
                'rule' => 'required|in:always_open,closes_at_first_round,specific_period'],
            ['group' => 'requirements', 'key' => 'registration_opens_at', 'type' => 'datetime', 'nullable' => true, 'default' => null, 'section' => 'Registration',
                'label' => 'Registration Opens At', 'help' => 'Only used when Registration Closes is "Specific period".'],
            ['group' => 'requirements', 'key' => 'registration_closes_at', 'type' => 'datetime', 'nullable' => true, 'default' => null, 'section' => 'Registration',
                'label' => 'Registration Closes At', 'help' => 'Only used when Registration Closes is "Specific period".'],
            ['group' => 'requirements', 'key' => 'waitlist_enabled', 'type' => 'boolean', 'default' => false, 'section' => 'Registration',
                'label' => 'Waiting List', 'help' => 'Once full, new entries join a waiting list instead of being rejected. A spot that opens up is automatically taken by whoever is next on the list.'],

            // --- Penalties & Rating ---
            ['group' => 'penalties', 'key' => 'stewarding_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Use XCL Stewarding', 'help' => 'Let this championship use XCL\'s report and steward workflow.'],
            ['group' => 'penalties', 'key' => 'affects', 'type' => 'enum', 'options' => ['points', 'rating', 'both', 'none'], 'default' => 'none', 'depends_on' => 'stewarding_enabled',
                'label' => 'Penalties Affect', 'help' => 'What a steward-issued penalty changes. Only used when stewarding is on.', 'rule' => 'required|in:points,rating,both,none'],
            ['group' => 'penalties', 'key' => 'post_race_time_penalties_enabled', 'type' => 'boolean', 'default' => false,
                'label' => 'Post-Race Time Penalties', 'help' => 'Allow a time penalty to be applied to a result after the race.'],
            ['group' => 'penalties', 'key' => 'xcl_rating_requested', 'type' => 'boolean', 'default' => false,
                'label' => 'Request XCL Rating', 'help' => 'Raises a request for an XCL admin to review. It does not turn rating on by itself — only an admin can approve it.'],

            // --- Balance (rendered on the Penalties & Balance step) ---
            ['group' => 'balance', 'key' => 'adjustments', 'type' => 'list', 'default' => [],
                'label' => 'Ballast & Restrictor Adjustments', 'help' => 'Per-driver or per-car overrides. Stored as a growing list rather than fixed columns.'],
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
            Arr::set($defaults, $field['group'] . '.' . $field['key'], $field['default']);
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
            if (!in_array($field['group'], $groups, true) || $field['type'] === 'list') {
                continue;
            }

            $key = $prefix . '.' . $field['group'] . '.' . $field['key'];
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
            return $count === 0 ? 'None set' : $count . ' set';
        }

        if ($value === null || $value === '') {
            return 'Not set';
        }

        return match ($field['type']) {
            'boolean' => $value ? 'Yes' : 'No',
            'enum'    => ucfirst(str_replace('_', ' ', (string) $value)),
            'time'    => (string) $value,
            'float'   => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
            default   => (string) $value,
        };
    }

    private static function inferredRule(array $field): string
    {
        $nullable = ($field['nullable'] ?? false) ? 'nullable' : 'sometimes';

        return match ($field['type']) {
            'boolean'  => 'boolean',
            'integer'  => $nullable . '|integer',
            'float'    => $nullable . '|numeric|between:0,1',
            'enum'     => $nullable . '|in:' . implode(',', $field['options'] ?? []),
            'text'     => $nullable . '|string|max:5000',
            'datetime' => $nullable . '|date',
            default    => $nullable . '|string|max:255',
        };
    }
}
