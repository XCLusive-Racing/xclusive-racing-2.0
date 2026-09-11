# Championships Platform — Implementation Plan

## Rule for all sessions working from this document

Read this file first, before doing anything else on this feature. Work only on the
phase marked as current in **Current State** below — do not start a later phase
early, even if it looks quick or related. As tasks are completed, tick their
checkboxes in place. Before ending a session, update **Current State** to say
which phase is current and what was last completed, so the next session can pick
up without re-deriving context.

## Current State

- **2026-09-11, tenth follow-up — Format step reorganized: Classes builder
  moved up, Car Class hides under Multiclass, eligible cars are real cars.**
  Three user-directed pieces:
  1. The Classes builder ("+ Add Class") was stranded at the very bottom of
     the Format step, below Driver Swaps — moved to render immediately after
     the Multiclass toggle field itself in `wizard.blade.php`'s field loop
     (`_classes-builder.blade.php` already show/hides on that same toggle,
     unchanged there).
  2. Car Class (the single, non-multiclass car-class option) now hides once
     Multiclass is on instead of sitting there looking equally relevant —
     `_field.blade.php`'s `depends_on` mechanism (added in the ninth
     follow-up, see below) gained a `!` inversion prefix
     (`'depends_on' => '!multiclass_enabled'`): shown while the toggle is
     OFF, hidden once it's ON. `data-shown-if`'s shared script in
     `wizard.blade.php` gained a matching `data-invert` attribute check.
  3. "Eligible cars" in the Classes builder was a raw comma-separated text
     field — now a real `<select multiple>` sourced from `App\Models\Car`
     scoped to the championship's own game, the same source
     `race/show.blade.php`'s own car picker already uses. Rows added via
     "+ Add Class" build the same select from a `@json()`-embedded car list
     (HTML-escaped per option — car names come from an admin-editable table,
     not hardcoded, so this isn't purely decorative).
  - A real DOM-testing lesson from this pass: the first attempt at
    `test_format_step_hides_car_class_once_multiclass_is_on` used a raw
    `assertSee()` string match against the rendered `hidden` attribute and
    kept failing — not because the feature was broken (three separate Tinker
    checks with proper `Auth::login()` context all confirmed the setting
    persisted and read back correctly), but because Blade's literal
    whitespace between adjacent `@if` directives doesn't collapse the way a
    single hand-written expected string assumes. Rewritten using
    `DOMDocument`/`DOMXPath` instead, which passed immediately — prefer that
    over exact-string `assertSee()` for anything checking an HTML *attribute*
    (not just visible text) from now on in this file.
  143 tests passing.
- **2026-09-11, ninth follow-up — no manual "Push Config" button on the
  Rounds list.** User-directed: `gportal:push-configs`/`gportal:import-results`
  already run globally on a schedule (`routes/console.php`, every minute),
  championship rounds included — the same automatic behaviour a regular
  event already gets (its own manual push button was removed back on
  2026-08-15 once the auto-push status card landed). The button in
  `_rounds.blade.php` is gone; the passive status text (config pending/
  pushed/failed) stays. The `rounds.push-config` route/controller/
  `PushRoundConfigJob` are untouched — still a working manual retry path,
  just not linked from this list. `ChampionshipRoundPushTest`'s render test
  renamed/inverted to confirm the button's absence. 141 tests passing.
- **2026-09-11, eighth follow-up — XCL Rating approval opened up to a
  league's own manager, no longer XCL-admin-only.** Real policy reversal,
  user-directed after confirming: `ChampionshipPolicy::approveRating()` was
  deliberately `canManage()`-only ("xcl_rating_enabled is never a League
  Manager's call, because the rating only means something because XCL
  controls what feeds it") — now the same gate as `update()`
  (`canManage() || managesLeague($championship->league)`).
  `ChampionshipWizardController::revokeRating()` switched from its own
  `canManage()`-only `abort_unless` to `Gate::authorize('approveRating', ...)`
  so it can't drift from the policy again. The Basics toggle and Review
  step's card both already read `$canApproveRating`/re-check the policy, so
  no view change was needed for either to now show a league manager the
  control too. Tenant isolation is unaffected — a manager of a *different*
  league still can't reach it (404s at route-model binding, same as every
  other cross-league test in this file, before the policy is even
  consulted). Tests replaced: `test_league_manager_cannot_reach_the_approve_rating_route`
  → `test_league_manager_can_approve_and_revoke_rating_for_their_own_championship`
  + `test_a_different_leagues_manager_still_cannot_approve_rating`;
  `test_basics_step_hides_the_rating_toggle_from_a_league_manager` →
  `test_basics_step_shows_the_rating_toggle_to_the_leagues_own_manager_too`.
  141 tests passing.
- **2026-09-11, seventh follow-up — the Basics-step enable/disable button
  405'd, and needed the wizard's purple styling.** Real bug: the enclosing
  Basics `<form>` carries `@method('PUT')` as a hidden `_method` field for
  its own normal save — that field rides along on *every* submit from
  inside the form regardless of the button's own `formmethod="POST"`, so
  Laravel's method-spoofing (reads `_method` on any POST body) still
  resolved it to PUT and 405'd against the POST-only approve-rating/
  revoke-rating routes. Fixed with an onclick that blanks `input[name=_method]`
  before the button's own submit proceeds. Also restyled to the same purple
  (`#7c3aed`) the rest of the wizard's toggles use, was green/neutral.
  XCL-R Multiplier's minimum also raised to 0.6 (was 0.1) alongside the
  existing 2.5 max — schema rule, all three round-mutating actions, both
  round-level HTML `min` attributes.
  `RoundCreationTest::test_xcl_r_multiplier_outside_0_6_to_2_5_is_rejected`.
  The `_method` fix itself has no automated coverage — it's a browser-side
  form-submission behavior PHPUnit's route-level tests don't exercise (they
  POST directly to the routes, bypassing the button/form entirely). 140
  tests passing.
- **2026-09-11, sixth follow-up — XCL-R Multiplier capped at 2.5x** (was
  10x, matching the unrelated race-wizard Custom Race field's own cap — left
  that one alone, not part of this ask). Schema rule, all three of
  `ChampionshipWizardController`'s round-mutating actions, and the HTML
  `min`/`max` attributes on the two hand-coded round-level inputs
  (`_round-shared-fields.blade.php`/`round-edit.blade.php`) all updated
  together. `RoundCreationTest::test_xcl_r_multiplier_above_2_5_is_rejected`.
  140 tests passing.
- **2026-09-11, fifth follow-up — XCL Rating enable/disable toggle added to
  Basics, as an in-form option, not a card below Save & Continue.** First cut
  put it in its own `<div class="admin-card">` after the closing `</form>` —
  user feedback: "hij moet als optie ertussen staan, hij staat nu onder
  save" — moved into `_basics.blade.php` itself, its own subsection between
  Description and Server, same visual treatment as everything else on the
  step. Still a separately-submitted action (approve-rating/revoke-rating,
  not the step's own `wizard.update` save) even though it can't be a nested
  `<form>` inside the Basics one — done with `formaction`/`formmethod` on the
  button itself (HTML5 lets one submit button in a form override where *that
  button's* click submits to, everything else in the form still goes to the
  form's own action). Same admin-only policy/routes as before, no change to
  the authorization boundary a league manager still can't cross. Also closes
  the loop on XCL-R Multiplier's disabled state, which was already reading
  `$championship->xcl_rating_enabled` live in `_field.blade.php` but had no
  in-wizard way to flip that switch other than Review.
  `ChampionshipSettingsTest::test_basics_step_shows_the_rating_toggle_for_an_admin_and_unlocks_the_multiplier`/
  `test_basics_step_hides_the_rating_toggle_from_a_league_manager`. 139 tests
  passing.
- **2026-09-11, fourth follow-up — Fixed Stop Time simplified to a plain
  on/off button, no number field at all.** User-directed: "off = standaard
  game, on = 25 seconds" — min_stop_secs is no longer admin-entered anywhere
  (championship-wide Sessions step or per-round). The schema field is still
  declared (`'hidden' => true`, new filter in `wizard.blade.php`'s
  `$visibleFields`) purely so it keeps a tracked default/upgrade path; its
  value is now always derived from `fixed_stop_time` — 25 when on, null when
  off — in `applyStepSettings()`'s sessions-step branch and all three of
  `ChampionshipWizardController`'s round-mutating actions. Help text under
  the toggle spells out what on/off mean. Round-level number input + its
  show/hide JS removed from `_round-shared-fields.blade.php` and
  `round-edit.blade.php`. `RoundCreationTest` updated (25 instead of an
  admin-supplied value; `min_stop_secs` no longer posted in the dynamic-case
  test since nothing reads it anymore). 137 tests still passing.
- **2026-09-11, third follow-up — item 9 generalized from grey-out to real
  show/hide, applied wherever a field only matters if another boolean is
  on.** User clarified item 9 further after the punch-list pass below: not
  just Driver Swaps greyed out, but a real show/hide "button" pattern
  ("als hij dat niet wil kan hij ze weer uitklikken en dan verdwijnen de
  velden ook"), and generalized to every such pair in the wizard, not just
  Format. Replaced `SECTION_DEPENDENCIES` (section-level, grey+disable) with
  per-field `'depends_on' => '<boolean key, same group>'` on the schema field
  itself — `_field.blade.php` wraps a dependent field in
  `data-shown-if="<toggle's id>"` (rendered already-hidden server-side if the
  dependency is currently off, so there's no flash of it before JS runs);
  one shared script in `wizard.blade.php` shows/hides it live on the toggle's
  change event. Hiding does **not** disable the input — its value still
  submits and round-trips unchanged, so flipping the toggle back on restores
  whatever was there instead of silently losing it (the one deliberate
  exception is `min_stop_secs`, which still gets explicitly nulled server-side
  when Fixed Stop Time is off — a real business rule, not just a display
  concern). Applied to: `driver_swaps_enabled` → the whole Driver Swaps
  section, `practice_enabled`/`qualifying_enabled` → their own length
  fields, `fixed_stop_time` → `min_stop_secs`, `stewarding_enabled` →
  `affects`. XCL-R Multiplier's XCL-Rating-gating stayed disabled+greyed in
  place (not this mechanism) since `xcl_rating_enabled` has no in-page toggle
  to hide/show against. 137 tests still passing, no test changes needed.
- **2026-09-11, second follow-up — a 9-item punch list from browsing the
  restyled wizard, all resolved except item 10 (see below, explicitly
  deferred pending its own plan).** Schema `CURRENT_VERSION` now 7.
  1. **In-game vs. real-world start time split.** `schedule.time_of_day`
     ("Real-World Start Time") only ever fed `Championship::scheduledDateTimeForRound()`'s
     real-world scheduling suggestion; the round's own separate in-game clock
     field was accidentally defaulting from that same value. New
     `sessions.ingame_time_of_day` is the correct, independent default now.
  2. **Formation Lap options fixed**: was `none/formation/rolling_start`
     (default `formation`) — a setting that was never actually consumed
     anywhere (`AccServerConfigService` doesn't read it), so purely a wording
     fix — now `short/full` (default `full`), per explicit user correction.
  3. **Rain Level is a standalone option again**, not hidden behind
     Weather=wet/mixed — a league can want a rain chance regardless of the
     fixed/random weather pick. Round-level `#…-rain-level-wrap` visibility
     JS removed in both `_round-shared-fields.blade.php` and `round-edit.blade.php`.
  4. **Pitstop time is now an explicit Fixed Stop Time checkbox**, not
     inferred from whether Min. Stop Time happens to be filled in.
     `AccServerConfigService` is unchanged — it still only reads
     `min_stop_secs` (`isRefuellingTimeFixed = !empty(...)`) — the new
     `fixed_stop_time` field/checkbox exists purely so "dynamic" genuinely
     nulls that column instead of leaving a stale prior value
     (`ChampionshipWizardController`'s three round-mutating actions and
     `applyStepSettings()`'s 'sessions' step both enforce this).
  5. **XCL-R Multiplier only usable once XCL Rating is enabled** for the
     championship (`$championship->xcl_rating_enabled`) — rendered `disabled`
     + greyed on the Sessions step and in Add/Edit Round otherwise. Backend
     save path is unchanged (not blocked server-side) — a disabled input
     simply never submits a value in the normal flow; not treated as a
     security boundary.
  6. **Practice Server option removed entirely** — leagues all run their own
     servers, so XCL's single shared practice server never applied here.
     Reverted `has_practice_server`/`practice_notes` from the schema, the
     `PracticeServerSessionManager`/`PracticeWindowNotOverlapping` wiring in
     `ChampionshipWizardController`, and the round-level UI.
  7. **Bulk Add Rounds decluttered** — turned out to be the same fix as item
     8 below, since both single and bulk mode render the same
     `_round-shared-fields.blade.php` partial.
  8. **Add/Edit Round no longer shows every session/weather/timing field
     always-expanded** — those already have a championship-wide default (set
     once on the Sessions step); showing them again on every single round was
     the actual "too much" complaint. Now a collapsed `<details>` "Override
     Session Defaults for This Round" panel (closed by default on Add Round,
     open by default on Edit Round since there's existing state worth
     seeing) — one click away, not gone.
  9. **Format step: fields belonging to an off toggle are now greyed out**,
     not shown at equal visual weight regardless of state. New
     `ChampionshipSettingsSchema::SECTION_DEPENDENCIES` (currently just
     `'Driver Swaps' => 'driver_swaps_enabled'`) — `wizard.blade.php` renders
     the gating toggle normally and wraps its dependents in a
     `[data-depends-on]` container a small shared script greys out
     (`opacity` + `disabled`) and re-enables live on toggle, no reload
     needed. Classes' own visibility (multiclass_enabled) already worked this
     way via `_classes-builder.blade.php`'s existing show/hide JS — untouched.
  - **Explicitly deferred, not attempted this pass — item 10**: "wizard-navigatie
    zoals event creation" — replacing the current per-step-page-save flow
    with a single-page, no-per-step-save stepper like the race form. This is
    a genuine architecture change (consolidating every step's validation and
    side effects — car-number/class sync, team-entry sync, points-scheme
    application — into one final submit, or building a very different
    partial-save model) that deserves its own scoping pass rather than being
    bolted on at the end of an already-large session. Flagged to the user;
    pick this up as its own piece of work.
  - Tests: `RoundCreationTest::test_dynamic_pitstop_time_clears_any_submitted_min_stop_secs`
    (and the earlier `test_single_round_creation_carries_the_new_event_maker_options`
    updated for the new `fixed_stop_time` field);
    `ChampionshipSettingsTest::test_sessions_is_its_own_step_independent_of_basics`
    updated for `formation_lap_type`'s new options and the new required
    `ingame_time_of_day`. 137 tests passing.
- **2026-09-11, follow-up — Sessions split back out into its own wizard step.**
  User feedback right after the restyle below: "ik wil niet dat 1 kopje heel
  veel staat en dan bij andere bijna niks" — the event-maker-parity additions
  had pushed Sessions (now 16 fields: lengths, weather, the new multiplier/
  pitstop/practice-server fields) to ride along on the already-busy Basics
  step (~28 fields total with Identity/Branding/Description/Server/Schedule),
  while Penalties had only 4. `ChampionshipSettingsSchema::STEPS`/
  `STEP_GROUPS` gained a `sessions` step of its own (order: Basics → Sessions
  → Rounds → Format → Scoring → Requirements → Penalties → Review — the
  `rounds`/`{step}` route `where()` constraint needed `sessions` added too,
  easy to miss since `STEPS`/`STEP_GROUPS` alone don't guard the route).
  Every `sessions` field also got a `'section'` tag (Session Lengths /
  Weather / Rating & Pitstops / Practice Server) so the new step itself isn't
  one more undivided wall of fields.
  `tests/Feature/ChampionshipSettingsTest.php::test_sessions_is_its_own_step_independent_of_basics`.
  136 tests passing.
- **2026-09-11, championship maker restyle + event-maker option parity** (see
  `C:\Users\PC Olle\.claude\plans\mellow-leaping-bachman.md` for the approved
  plan this implemented): the championship-level wizard steps (Basics/Format/
  Requirements/Sessions, rendered by `_field.blade.php` off
  `ChampionshipSettingsSchema`) restyled with toggle pills for boolean fields
  (matching `admin/users/edit.blade.php`'s role pills — one shared script in
  `wizard.blade.php`), kept the existing multi-page-per-step architecture
  (a JS single-page rebuild like the race form was considered and rejected —
  bigger risk, no functional gain for a 30+-field settings form).
  `CURRENT_VERSION` bumped to 6 with real missing options added end-to-end
  (schema field → round creation → the service that actually reads it):
  - Basics: `iracing`/`ac` added to the Game select (previously acc/lmu only);
    `Championship.description` (a real, already-fillable column) exposed in
    the UI for the first time; the plain file-upload banner swapped for
    `<x-media-picker>` (same `resolveMedia()` pattern as the legacy native
    championship forms).
  - Sessions group: `xcl_r_multiplier`, `pitstop_count`/`min_stop_secs`,
    `has_practice_server`/`practice_notes` — all previously entirely absent
    from league championships. **Real rating-fairness bug found and fixed**:
    without an `xcl_r_multiplier`, `RatingService::processRace()`'s fallback
    chain (format multiplier → custom multiplier → legacy `duration_key` →
    1.0) landed every championship round on a flat 1.0 regardless of length,
    since a round has neither an `EventFormat` nor this field. Still manual
    (like the race form's own field — no auto-derivation from length exists
    anywhere in the app to reuse).
  - Format/Driver Swaps section: `driver_stint_time_mins`/
    `max_total_driving_time_mins`/`mandatory_driver_swap` added alongside the
    existing `driver_swaps_enabled`. **Real backend gap found and fixed**:
    `AccServerConfigService::eventRules()` only ever applied these three
    values when `$race->is_endurance` was true — a column championship rounds
    never carry (Custom-Race-only by the Phase 4 scope decision). New
    `isDriverSwapRace()` broadens the gate to also cover a championship round
    whose `settings.format.driver_swaps_enabled` is on — same class of fix as
    `RaceController::show()`'s `$isTeamRace` from the previous session.
  - Requirements: `max_xcl_rating_tier` added alongside the existing
    `min_xcl_rating_tier` — threaded through
    `Championship::requirementThresholds()`'s `'max'` key into
    `User::requirementFailure()`'s existing (previously always-null for
    league championships) 4th parameter. Native XCL championships have no
    max-rating column of their own, so `'max'` is always `null` there — out
    of scope, not requested.
  - All of the above also added to Add/Edit Round and Bulk Add Rounds
    (`_round-shared-fields.blade.php`, `round-edit.blade.php`,
    `ChampionshipWizardController::resolveRoundRow()`), pre-filled from the
    championship-wide defaults but overridable per round — same pattern
    weather/rain/ambient_temp already used. `has_practice_server` also wired
    to `PracticeServerSessionManager::sync()` (with the race form's
    `PracticeWindowNotOverlapping` validation on the two single-round paths;
    **known, accepted gap**: bulk-added rounds don't validate practice-server
    window overlaps against each other, since one shared flag across many
    generated dates makes a per-row check disproportionate to how rarely
    that combination will actually be used).
  - Tests: `tests/Feature/RoundCreationTest.php` (new field passthrough +
    the `AccServerConfigService` gate fix), `tests/Feature/
    ChampionshipRegistrationTest.php` (max-rating-cap blocking),
    `tests/Feature/ChampionshipSettingsTest.php` (Basics step: new games,
    description, gallery-picked image). 135 tests passing (was 131).
  - **Not done, explicitly scoped out**: per-class `max_rating` (only
    championship-wide); a single-page JS-driven wizard rebuild (rejected,
    see above); enum fields as pill-buttons instead of `<select>` (only
    booleans were restyled — enums were judged good enough as-is given the
    time this would add for comparatively little visual gain; revisit if the
    user still finds them inconsistent after seeing the boolean pills).
- **2026-09-11 follow-up work, outside the phase/batch numbering below**
  (not yet folded into the checklists themselves — noted here so it isn't
  lost like the archive/delete work was):
  - League archiving was hardened to a real soft delete (owner-role-only),
    plus a genuine permanent delete for an already-archived, championship-free
    league — `Admin\LeagueController`. Leagues list is now a DataTable.
  - **Whole-championship team registration** (`settings.format.team_registration_scope
    = 'championship'`) now lets a team opt out of a single round without
    leaving the championship: the per-round `RaceTeamEntry` auto-created by
    `ChampionshipTeamEntryService` is a normal, removable entry — the
    round's own public page (`race/show.blade.php`) now shows the TEAM ENTRY
    card (with its existing REMOVE button) for a driver-swaps championship
    round too, not just a Custom-Race `is_endurance` event. See
    `RaceController::show()`'s `$isTeamRace`/`$isChampionshipTeamRound`, and
    `tests/Feature/ChampionshipRegistrationTest.php`'s
    `test_championship_scope_team_can_opt_out_of_a_single_round_*`.
  - **Discord "Require membership to register" toggle** (League edit,
    admin-only) is now rendered disabled/greyed-out rather than removed —
    the code path (Phase 4) is complete, but XCL hasn't finished installing
    its bot into any real league's Discord server yet, so a league
    shouldn't be able to turn on enforcement no one's tested end-to-end.
    Existing values pass through unchanged via a hidden field. Re-enable by
    dropping the `disabled` attribute once operational setup happens.
  - **Rating engine: co-driver double-counting fixed** (the Phase 5c gap
    noted in the Team feature — see the project's `project_team_feature_plan`
    memory). `XclRating::processRace()` now groups entries by an optional
    `field_key` (co-drivers sharing one car) for every field-relative number
    — SoF, finisher count, rFactor scale, the win-% pool — so one car with
    two rated humans no longer inflates the field or skews SoF for the whole
    race. Each co-driver still gets their own individually-computed Elo
    change from their own rating against that (now-correct) SoF — a
    higher-rated co-driver still earns less / loses more than a lower-rated
    one for the identical result, same mechanism as any two solo drivers.
    `RatingService::processRace()` supplies `field_key` from the result's
    registration `team_entry_id` (falling back to `car_number`, same
    precedence as `RaceResult::groupedByCar()`). Covered by
    `tests/Unit/XclRatingTest.php` (previously zero unit coverage on this
    math at all).
  - **ACC PC / ACC Console rating decision made — first step of the ACC PC
    integration**: one shared rating board. Racing on either platform reads
    and writes the same `elo_acc`/`sr_acc` columns. New `User::ratingGame()`/
    `eloColumn()`/`srColumn()` are now the single place that decision lives;
    `requirementFailure()`, `rank()`, `ratingClass()`, `srGrade()`,
    `RatingService`'s field lookups, `Report::ratingFields()`, and
    `race/show.blade.php`'s SoF/sort calculations all route through it
    instead of their own `"elo_{$game}"` string-building (which silently
    produced nothing for `ac` before this). Practical effect: ACC PC races
    are now actually rated, entry requirements enforced, and driverCategory
    banners computed — none of that worked before. Full ACC PC integration
    (its own branding/assets, actual platform-specific server behaviour) is
    still open, this was only the rating-model decision.
- **All seven phases are now built (2026-09-10)**, plus a second refinement
  batch (below) — items 1-4 and 6-9 are all done. Item 5 (Discord
  operational setup) is explicitly deferred by the user; nothing else is
  open in this batch. Next session should pick a new area of work rather
  than continue this list.
- **Real, previously-latent bugs found and fixed along the way, worth
  knowing about even though they're already fixed**: a missing
  `ChampionshipClass::isFull()` that would have thrown on any full
  multiclass entry; the wizard's multiclass toggle never reaching the real
  `is_multiclass` column; a live report/rating pipeline with zero test
  coverage before Phase 6 touched it; and, in Phase 7, the public
  championship page's entire league-branding feature silently never
  rendering for an actual public visitor since Phase 2 shipped it (fixed by
  eager-loading `league` past its own tenant scope), plus a second
  Discord-requirement opt-in (`settings.requirements.discord_membership_required`)
  that Phase 4's enforcement never checked.
- **No phase is "in progress."** Next session should decide what to pick up
  next: one of the deliberately-open items above, or start fresh work
  beyond this plan's original seven phases.
- **Also completed out of order (2026-09-09), same session:** the Basics step
  gained a Server default (`championships.ftp_server_id`) and a Schedule
  group (`start_date`/`recurrence`/`day_of_week`/`time_of_day` in
  `settings.schedule`, `Championship::scheduledDateTimeForRound()`), and the
  standalone Sessions wizard step was folded into Basics — both explicitly
  requested directly, ahead of Phase 3's own round-prefill item.
- **Last completed:** Phases 1 and 2, implemented and tested (2026-09-08).
  Phase 1: `league_manager`/`league_steward` roles, `League`/`LeagueUser`/
  `AuditLog` models, the `Tenantable` concern + `TenantScope` global scope,
  `league_id` added to `ftp_servers` with credentials encrypted and never
  rendered back, and the `/admin/leagues` admin section (full CRUD + member
  assignment for XCL admins, branding-only edit for League Managers, read-only
  for League Stewards). Phase 2: `championships` table extended in place with
  `league_id`, `slug`, `platform`, `visibility`, `starts_at`/`ends_at`,
  `xcl_rating_enabled` + approval fields, and a versioned `settings` JSON column
  (`ChampionshipSettingsSchema`, `ChampionshipSettings`, `ChampionshipSettingsCast`);
  a `points_schemes` table with seeded FIA/SimGrid-style templates; the
  `ChampionshipPolicy` + `ApproveChampionshipRatingRequest` gating the rating
  approval; and the 7-step setup wizard (Basics, Format, Sessions, Scoring,
  Requirements, Penalties & Balance, Review) under
  `/admin/leagues/{league}/championships/{championship}/wizard/{step}`.
  62 feature tests passing (only the pre-existing, unrelated `ExampleTest`
  fails — a stock Laravel scaffold test with no DB setup, not something this
  work touches). See the two "Implementation notes" call-outs below for the
  judgment calls made along the way.

---

## Summary

XCLusive Racing (XCL) currently runs sim racing events for its own members. This
feature turns the platform into a host for other sim racing organisations —
starting with NLRL, SRC and EER — to run their own championships inside XCL
rather than beside it. Each organisation is a **league**: its own identity, its
own managers, its own rules, its own members, fully isolated from every other
league's data even though everyone shares the same admin panel and database.

A **league manager** is an external, untrusted user who gets a League Manager
role and a scoped admin section. Through a **championship setup wizard** (built
to feel like XCL's existing race-creation wizard) they configure game/platform,
schedule, format, multiclass, entry counts, driver swaps, session lengths,
weather, points, drop rounds, entry requirements, ballast/restrictor
adjustments, and penalty handling. They can optionally opt into XCL's
stewarding system and, only if an XCL admin grants it, XCL Rating. Leagues bring
their own race servers, added to XCL's FTP push system, starting with ACC on
console and PC (Le Mans Ultimate later — the design must not assume ACC
everywhere). Publicly, visitors pick a league from a championships area and land
in a space themed for that league (logo, colours, Discord link) listing its
championships, each with standings, entry requirements, rules, penalties, and
prizes — clearly XCL, clearly that league's home.

This is a starting point, not a finished spec. The architecture must make adding
a new rule or option cheap, because leagues will keep asking for more.

---

## Architecture Summary

### What already exists and must be reused, not duplicated

**Roles & permissions** — fully custom, no ACL package. `roles`/`role_user`
pivot tables (`database/migrations/2026_06_02_100000_create_roles_tables.php`),
`App\Models\Role`, and `User::roles()`/`hasRole()`/`hasAnyRole()`
(`app/Models/User.php:90-147`) with named ability methods (`isOwner`, `isAdmin`,
`canManage()`, etc.) — this is the idiom to extend for `league_manager`, not a
new pattern. Enforcement is `Route::middleware(['auth','admin'])` (checks
`canManage()`) plus a generic `role:slug1,slug2` middleware
(`app/Http/Middleware/{IsAdmin,HasRole}.php`, aliased in `bootstrap/app.php:17-20`).
**Nothing today does row-level / tenant scoping** — roles are global flags on
the user, not scoped to a subset of data. The closest analogue is
`RacingTeam` (`owner_id` + `members()` pivot + `hasMember()`,
`app/Models/RacingTeam.php`), which scopes access to one team's roster — a
useful pattern reference, not directly reusable for league scoping.

**"Event" is `Race`, and a single-org Championship already exists end-to-end** —
there is no separate `Event` model. `App\Models\Race` (`app/Models/Race.php`)
is the event entity, built up via ~25 incremental migrations, with a
`config_overrides` JSON column, FTP/push-status fields, and relations to
`EventFormat`, `FtpServer`, `RaceClass`, `RaceRegistration`, `RaceTeamEntry`,
`RaceResult`. **Important:** `App\Models\Championship` /
`ChampionshipClass` / `ChampionshipRegistration` / `ChampionshipPenalty`
already implement an XCL-native, single-organisation championship feature —
seasons, points systems, bonus points, drop rounds, multiclass standings, and
manual penalty deduction, all working
(`Championship::computeStandings()`/`computeClassStandings()`,
`app/Models/Championship.php:98-211`). A championship's "rounds" are just
`Race` rows with `championship_id`/`round_number` set. **This plan's default
position is to extend these existing models with league-awareness rather than
build a parallel set** — see Phase 2. Treat this as the current baseline, not
greenfield.

**The create-race wizard** — `Admin\RaceController` (1100+ lines) +
`resources/views/admin/races/form.blade.php` (1800+ lines): plain Blade +
vanilla JS, not Livewire/Inertia/Alpine. Wizard state is DOM-based
(`data-step-panel`/`data-step-nav` toggled by JS), 4 steps: Game & Format,
Schedule, Drivers (requirements/multiclass/endurance), Details. Entry
requirements are enforced via `User::requirementFailure()`
(`app/Models/User.php:46`) reading `sr_requirement`/`min_rating`/`max_rating`
columns. Multiclass is `RaceClass` (near-identical sibling of
`ChampionshipClass`). Ballast/restrictor is a separate standing reference
table, `Bop` (`app/Models/Bop.php`), keyed by game+car+track, not race-specific.
The championship setup wizard should look and feel like this one.

**FtpServer / FtpService / AccServerConfigService** — `FtpServer`
(`app/Models/FtpServer.php`) already encrypts `password` at rest via Laravel's
`encrypted` cast; `username`/`host` are plain text. It has **no** league/org,
game, or platform column — the ACC-console assumption is baked into hardcoded
defaults inside `AccServerConfigService`, not schema. `FtpService`
(`app/Services/FtpService.php`) is a raw-cURL FTP client (despite
`league/flysystem-ftp` being in `composer.json`, it isn't used).
`AccServerConfigService` (`app/Services/AccServerConfigService.php`) is a pure
ACC-json-shape transform with no console-vs-PC branching. Config pushes happen
via two paths — scheduled (`app/Console/Commands/PushGPortalConfigs.php`) and
manual (`Admin\RaceController::pushConfig`, line 903) — both **synchronous**,
unlike practice-server pushes which are a real queued job
(`app/Jobs/PushPracticeServerConfigJob.php`).

**XCL Rating** — no separate rating-history model; current rating lives as flat
`elo_{game}`/`sr_{game}` columns on `User`. Calculation is
`App\Services\XclRating::processRace()` (pure Elo math) orchestrated by
`App\Services\RatingService::processRace()`, called from exactly two places
(`AccResultImportService.php:66`, manual recalc in
`Admin\RaceResultController.php:324`). **No opt-in/opt-out concept exists** —
any race in a ratable game is rated unconditionally.

**Stewarding / penalties — two existing, disconnected mechanisms.** (1) A full
report/steward workflow: `Report` + `ReportVerdict` + `PenaltyCalculator`
(`app/Models/Report.php`, `app/Services/PenaltyCalculator.php`,
`Admin\ReportController.php`) — drivers file reports, two stewards independently
rule, a matching verdict pair applies a rating/SR deduction **by mutating
`User.elo_{game}`/`sr_{game}` directly**, bypassing `RatingService` entirely. It
never touches finishing position or points. (2) `ChampionshipPenalty` — a flat
points deduction with no connection to (1) at all. **No automatic
position/time recalculation exists anywhere** — penalties today only ever
adjust rating, SR, or points, never finishing order.

**Registration & teams** — auth is fully custom (Socialite for
Discord/Steam, a bespoke Xbox flow; no Breeze/Fortify). Per-round signup is
`RaceRegistration`; team/driver-swap entries are `RaceTeamEntry`
(`racing_team_id`, `car_number`, `starting_driver_id`, hasMany
`RaceRegistration` for the swap roster) tied to `RacingTeam`
(owner + members). Championship-level registration
(`ChampionshipRegistration`) is individual-only today — there is no
championship-level team-entry concept yet, only per-race.

**Public pages** — `ChampionshipController` (public) and `CalendarController`
render a single flat list under one sitewide XCL brand
(`config('xcl.name')`); there is no organisation/brand concept anywhere in the
public views. `RacingTeam` has its own logo but is unrelated to event
organisers.

**Discord** — `ConnectedAccount` links a user's Discord identity;
`SyncDiscordRankRole` syncs a **single**, XCL-owned Discord guild's rank role.
There is no existing way to check membership of an arbitrary league's guild —
this is new integration work (see Open Questions).

### New concepts this feature introduces

- **League** — new model: identity, branding, managers, own Discord, own
  servers, owns championships.
- **Tenant isolation** — new: global Eloquent scopes on every league-owned
  model, plus league-scoped route/middleware guards. Nothing like this exists
  today; roles are flat, not row-scoped.
- **Versioned championship settings schema** — new: a JSON `settings` column on
  `championships` validated by a schema class, replacing ad hoc flat rule
  columns as the extension point for future rules.
- **XCL Rating eligibility flag** — new: admin-only, per-championship, never
  league-manager-settable.
- **Unified penalty-effects setting** — new: per-championship choice of whether
  steward-issued penalties affect points, rating, both, or neither, bridging
  the two currently-disconnected penalty mechanisms.
- **Post-race time-penalty result adjustment** — new: no equivalent exists
  today.

### Decisions and their reasoning

1. **Tenant isolation is enforced by global scopes on models, not by hiding
   elements in the UI.** League managers operate inside our real admin panel,
   not a sandbox — a forgotten `where` clause must not be able to leak one
   league's data into another's screen.
2. **XCL Rating eligibility is granted per championship by an XCL admin and can
   never be self-selected by a league manager.** The rating only means
   something because XCL controls what feeds it — this must be enforced
   server-side (validation on the league-manager route must never accept the
   field), not merely hidden from the wizard UI.
3. **Championship rules live in a versioned JSON settings object validated
   against a schema class, not as database columns.** New rules will be
   requested indefinitely; every new rule must cost a schema entry, not a
   migration.
4. **League FTP credentials are encrypted at rest and are never rendered back
   to the browser in any form.** `FtpServer.password` already does this via
   Laravel's `encrypted` cast — extend the same treatment to `username`/`host`
   for league-owned servers and audit every edit view to confirm no field ever
   echoes a decrypted credential back into a form value.

---

## Phase 1 — Roles, the League model and tenant isolation ✅ complete (2026-09-08)

- [x] Add a `league_manager` role via migration + the existing roles seeder
      pattern (`database/migrations/2026_06_02_100000_create_roles_tables.php`,
      `app/Models/Role.php`). Implemented as two roles, `league_manager` and
      `league_steward` (`database/migrations/2026_09_08_000001_add_league_roles.php`)
      — the task asked for both.
- [x] Create `leagues` table + `League` model: `name`, `slug`, `logo`,
      `primary_color`, `secondary_color`, `discord_guild_id`,
      `discord_invite_url`, `status` (draft/active/suspended), `description`.
      Schema only in this phase — public UI comes in Phase 7. Implemented as
      `name`, `slug`, `logo`, `banner`, `primary_color`, `accent_color`,
      `description`, `discord_invite_url`, `website_url`,
      `requires_discord_membership`, `status` (draft/active/archived),
      soft deletes (`app/Models/League.php`,
      `database/migrations/2026_09_08_000002_create_leagues_table.php`) — the
      final column set came from a later, more specific task message than this
      bullet's draft one.
- [x] Create a `league_managers` pivot (`league_id`, `user_id`, timestamps).
      Implemented as `league_user` (`league_id`, `user_id`, `role` string —
      manager|steward — timestamps, `app/Models/LeagueUser.php`) so one pivot
      carries both roles per the later task message, rather than a
      manager-only pivot.
- [x] Add `User::leagues()` (belongsToMany) and `User::managesLeague(League $league): bool`,
      following the existing `isX()`/`canX()` naming idiom on `User`
      (`app/Models/User.php:90-147`). Also added `stewardsLeague()`,
      `leagueIds()`, `isLeagueManager()`/`isLeagueSteward()` (the global role
      flags), and `syncLeagueRoleFlags()` (keeps those flags in step with
      `league_user` membership whenever it changes).
- [x] Add a league-scoped route guard/middleware that resolves the league from
      the route, and 403s unless the acting user manages that league or is XCL
      staff (`canManage()`). Model on `app/Http/Middleware/{IsAdmin,HasRole}.php`.
      Implemented as `App\Http\Middleware\LeagueAccess` (alias `league.access`)
      — gates entry to the `/admin/leagues` surface for canManage() OR
      league_manager/league_steward; which specific league(s) is enforced
      separately by the tenant scope, not this middleware.
- [x] Build a `LeagueScope` (or equivalent) global scope applied to every
      league-owned model, auto-constraining queries to the acting league
      manager's own league(s); XCL staff bypass it. This is the mechanism
      Decision 1 requires. Implemented as `App\Models\Concerns\Tenantable`
      (the concern) + `App\Models\Scopes\TenantScope` (the global scope) per
      the later task's explicit naming. See "Implementation note: the null
      tenant key" below for a subtlety in how it treats `league_id IS NULL`.
- [x] Feature test: a league-manager for League A hitting a League B resource
      by guessed/enumerated ID gets 403/404, not an empty screen — proving the
      scope holds even when the UI would have hidden the link.
      `tests/Feature/LeagueTenantIsolationTest.php` — covers direct model
      queries, HTTP routes (404 not 403 for cross-league), no-membership
      users, admin bypass, and the same isolation on `FtpServer`.
- [x] Decide and document the admin route namespace for league-manager-facing
      resources (e.g. `/admin/leagues/{league}/...`) versus XCL admin's
      existing flat `/admin/championships`, so the two audiences stay
      structurally distinct in `routes/web.php`. Decided: `/admin/leagues/...`,
      confirmed and reused in Phase 2 for `/admin/leagues/{league}/championships/...`.
- [x] Add a "League Manager" admin-panel nav section, visible only to users
      with the `league_manager` role, scoped to their own league(s) only.
      Implemented as a "Leagues" section (`resources/views/layouts/admin.blade.php`)
      visible to canManage() or league_manager/league_steward alike, since XCL
      admins need the same entry point for full league CRUD.

**Also done, beyond the original checklist wording, per the task that actually
arrived for this phase:**
- [x] `FtpServer` gained a nullable `league_id`, `Tenantable`, and `username`
      is now `encrypted` (widened to `text`) alongside `password` — neither
      is ever echoed back into an edit form value
      (`app/Models/FtpServer.php`, `resources/views/admin/servers/edit.blade.php`).
- [x] `audit_logs` table + `AuditLog` model + `App\Services\AuditLogger`,
      recording league and FTP server changes (`Admin\LeagueController`,
      `Admin\FtpServerController`).
- [x] Full `/admin/leagues` CRUD for XCL admins (create, edit, archive/restore,
      member assignment) and a branding/description/links-only edit screen for
      League Managers, with a league-context banner so a manager always knows
      which league they're acting in (`Admin\LeagueController`,
      `resources/views/admin/leagues/*.blade.php`).

## Phase 2 — The Championship model, its versioned settings schema and the setup wizard ✅ complete (2026-09-08)

- [x] Resolve the naming/ownership question up front: this plan's default is
      to **extend** the existing `Championship`/`ChampionshipClass`/
      `ChampionshipRegistration`/`ChampionshipPenalty` models
      (`app/Models/Championship.php` etc.) with league-awareness, rather than
      fork a parallel model — because their standings/points/drop-round logic
      already works in production. **Resolved as extend, not fork** — see
      "Implementation note: extending `championships`, not forking it" below
      for the reasoning and exactly what stayed untouched.
- [x] Add nullable `league_id` FK to `championships`; apply the `Tenantable`
      concern from Phase 1 (null = XCL's own native championship).
      `database/migrations/2026_09_08_000010_add_league_fields_to_championships_table.php`.
- [x] Add `settings` (JSON) and `settings_version` (integer) columns to
      `championships`, alongside — not yet replacing — the existing flat rule
      columns, so nothing existing (public show page, standings calc) breaks
      mid-migration. Done exactly as scoped — the legacy flat columns
      (`points_system`, `sr_requirement`, etc.) are untouched and still power
      `Championship::computeStandings()` for XCL's own native championships;
      only new, league-owned championships write into `settings`.
- [x] Build a `ChampionshipSettingsSchema` (or one class per settings section)
      defining every currently-known rule as a typed, versioned entry with
      validation and defaults for keys missing from an older-versioned blob —
      the mechanism Decision 3 requires. `app/Settings/ChampionshipSettingsSchema.php`
      — one class, not per-version subclasses; see the implementation note
      below on why that still satisfies the upgrade-path requirement.
      `app/Settings/ChampionshipSettings.php` (typed read view, group-then-key
      access) and `app/Settings/Casts/ChampionshipSettingsCast.php` (the
      Eloquent cast — upgrades on every read *and* every write, and stamps
      `settings_version`) complete the mechanism.
- [x] Cover at minimum in the schema: **superseded by the later, far more
      specific task message for this phase** — implemented exactly that
      message's six groups (format, sessions, scoring, requirements,
      penalties, balance) instead of this bullet's sketch. See "Schema keys
      defined" in the session's final report for the full field list and
      where each is consumed.
- [x] Add `xcl_rating_eligible` (boolean, default false) directly on
      `championships`, settable only via the XCL-admin controller path —
      strip/ignore it server-side on the league-manager route regardless of
      request payload (Decision 2). Implemented as `xcl_rating_enabled` +
      `xcl_rating_approved_at` + `xcl_rating_approved_by` per the later task's
      request/approve workflow (a league manager raises
      `settings.penalties.xcl_rating_requested`, an XCL admin approves).
      Enforced in three independent layers: the three columns are absent from
      `Championship::$fillable` (mass-assignment can't touch them from
      anywhere), `ChampionshipPolicy::approveRating()` is canManage()-only, and
      `ApproveChampionshipRatingRequest::authorize()` checks that policy before
      the controller ever runs — a League Manager cannot reach the route at
      all, let alone the field.
- [x] Build the league-manager championship setup wizard, modelled on
      `resources/views/admin/races/form.blade.php`'s plain-Blade +
      vanilla-JS multi-step pattern so it feels like the same product as the
      race wizard. `resources/views/admin/leagues/championships/wizard.blade.php`
      + step partials. One deliberate structural difference from the race
      wizard, required by this phase's "save progress per step" instruction —
      see the implementation note below.
- [x] Adapt `Admin\ChampionshipController`'s create/store/edit/update actions
      (`app/Http/Controllers/Admin/ChampionshipController.php`) as the
      starting point, reading/writing the settings schema and scoping every
      action to the acting league manager's own league. Built as a new,
      separate `Admin\ChampionshipWizardController` instead of adapting the
      legacy controller in place — the legacy one's create/store/edit/update
      are wired to the flat-column form and the standings/rounds features this
      phase was explicitly told not to touch; forking the controller kept that
      surface completely untouched while still reusing the model, the
      `league.access` middleware, and the `Tenantable`/policy stack.

**Also done, beyond the original checklist wording, per the task that actually
arrived for this phase:**
- [x] `points_schemes` table (`league_id` nullable = XCL template, `points_map`
      json, fastest-lap/pole points, `is_template`) + `PointsScheme` model
      (`Tenantable`) + `PointsScheme::copyFor()` + seeded FIA/SimGrid/linear
      templates (`database/seeders/PointsSchemeSeeder.php`). League managers
      copy a template via `Admin\PointsSchemeController@copy`; there is no
      edit route for a template at all, so "never by editing a shared one" is
      enforced by the route table, not a permission check.
- [x] `ChampionshipPolicy` (`app/Policies/ChampionshipPolicy.php`) — Laravel's
      first policy class in this codebase; auto-discovered by naming
      convention, no manual registration needed.
- [x] Full feature test coverage: `tests/Feature/ChampionshipSettingsTest.php`
      — settings defaults/upgrade path, a step save only touching its own
      settings group, the three-layer rating-approval gate (both the "cannot
      smuggle the field in" and "cannot reach the route at all" cases), and
      cross-league isolation on the new columns (including the admin-bypass
      URL-pairing guard in `ChampionshipWizardController::assertLeagueOfInterest()`).

### Implementation note: the null tenant key

`TenantScope` restricts a league-scoped user to `WHERE tenant_key IS NULL OR
tenant_key IN (their league ids)`, not just `IN (their league ids)`. This
matters for `FtpServer` (and now `Championship`): a row with `league_id = null`
is XCL's own, and stays visible to *everyone*, not just XCL staff — because
existing code (e.g. `RaceController::register()` building a driver's
registration-confirmation message from `$race->ftpServer`) depends on ordinary,
non-league-affiliated drivers being able to see XCL's own servers exactly as
they could before this feature existed. Without the null-bypass, every regular
driver would have lost that message the moment `FtpServer` gained a tenant
scope. `League` itself is unaffected by this (its tenant key is its own `id`,
which is never null, so a league-less user really does see zero leagues, as
required).

The same null-bypass reasoning is why extending `championships` (Phase 2)
worked out cleanly: XCL's existing native championships (`league_id = null`)
stayed universally visible — matching their current always-public behaviour —
the moment `Tenantable` was applied, with no special-casing needed.

A consequence worth flagging: any *console command or queued job* that touches
league-owned (non-null) rows without an authenticated user must call
`->withoutTenantScope()` explicitly, or it will only ever see the null-league
rows. Two existing console commands and one job needed this fix during Phase 1
(`PushGPortalConfigs`, `ImportGportalResults`, `PushPracticeServerConfigJob`) —
none of them touch league-owned servers yet, so this was precautionary, but
Phase 3's league-triggered pushes will need the same treatment wherever they
run outside a web request.

### Implementation note: extending `championships`, not forking it

The Phase 2 task, taken completely literally, said "create a championships
table" — but that table already exists (XCL's own in-house championship
feature: `Championship`/`ChampionshipClass`/`ChampionshipRegistration`/
`ChampionshipPenalty`, with working standings/points/drop-round logic). This
plan had already flagged that exact collision and pre-committed to extending
rather than forking (see the Architecture Summary above and Phase 2's first
checklist item). Given the instructions didn't ask to reopen that call, Phase 2
was implemented as an **additive migration** onto the existing table and
model:

- New columns only (`league_id`, `slug`, `platform`, `visibility`,
  `starts_at`/`ends_at`, `xcl_rating_*`, `settings`, `settings_version`, soft
  deletes) — every legacy column and every legacy method
  (`computeStandings()`, `computeClassStandings()`, `isFull()`,
  `isRegistered()`) is untouched.
- The legacy admin/public Championship controllers, views, and routes
  (`Admin\ChampionshipController`, `ChampionshipController`,
  `/admin/championships`, `/championships`) are untouched — they keep working
  for XCL's own championships exactly as before.
- `status` is a plain string column with no DB-level enum, so widening its
  *application-level* vocabulary (legacy: draft/active/finished; new:
  draft/published/registration_open/registration_closed/running/completed/cancelled)
  needed no schema change — the legacy controller's validation
  (`in:draft,active,finished`) still only ever writes its own three values,
  and the new wizard only ever writes the new seven, so the two vocabularies
  never collide in practice even though they share one column.

If a later session (or the person answering the Open Questions below)
decides XCL's own championships should become a first-class `League` row
instead of `league_id = null`, this is the point to revisit — the extension
approach depends on that null-bypass behaving the way `TenantScope` currently
defines it.

### Implementation note: the wizard's per-step persistence

The existing create-race wizard (`resources/views/admin/races/form.blade.php`)
is one page, one form, client-side JS panels, submitted once at the end. The
Phase 2 task explicitly asked for something the race wizard doesn't do: "save
progress per step so a half configured championship persists as draft."
That requires each step to actually round-trip to the server and persist
independently, not just show/hide a panel — so the championship wizard is
structured as one page *per step* (`GET/PUT .../wizard/{step}`), each a real
form submission, redirecting to the next step on success. The stepper nav
(numbered circles, connectors, active/done states, purple accent) visually
matches the race wizard closely, and the "same interaction patterns" brief is
followed wherever it doesn't conflict with resumability — the repeatable
class-list and ballast-adjustment builders use the exact same hidden-JSON-input
pattern as the race wizard's `classes_json` (add/remove rows in JS, serialize
to a hidden field on submit). The one deliberate divergence is per-step
persistence itself, which the resumable-draft requirement makes necessary.

## Phase 2.5 — XCL becomes a first-class League ✅ complete (2026-09-10)

> Inserted 2026-09-10, after resolving the "should XCL itself become a
> League row" Open Question below. `TenantScope` used to treat
> `league_id IS NULL` as "belongs to XCL, visible to everyone" — this
> phase replaced that null-bypass with a real, seeded XCL `League` row, so
> nothing in the pipeline is special-cased on null any more. Landed before
> continuing Phase 3, which is simpler to build now the tenant model is
> final.

- [x] Seeded a permanent "XCLusive Racing" `League` row (`slug`
      `xclusive-racing`), protected from accidental deletion via a new
      `is_system` boolean (`database/migrations/2026_09_10_000001_add_is_system_to_leagues_table.php`,
      deliberately absent from `League::$fillable`) — `Admin\LeagueController::archive()`
      now `abort_if($league->is_system, 403, ...)`. `points_schemes.league_id`
      keeps its `cascadeOnDelete()` FK as-is: harmless, since no route can
      hard-delete a `League` at all (`archive()` only flips `status`) and
      that one route is now guarded. `League::system()` is the one place
      that resolves this row (`withoutTenantScope()->where('is_system', true)->firstOrFail()`).
- [x] Backfill migration
      (`database/migrations/2026_09_10_000002_create_xcl_league_and_backfill_tenants.php`):
      creates the row (idempotent) and updates every `league_id IS NULL`
      row on `ftp_servers`, `championships`, `points_schemes` to it. Raw
      `DB::table()`, not Eloquent — those models' own `TenantScope` would
      otherwise hide rows from this no-auth migration context. Verified
      against the real dev DB after running: 0 null rows left on all
      three tables.
- [x] Removed `TenantScope`'s null-bypass branch entirely
      (`app/Models/Scopes/TenantScope.php`) — now
      `whereIn($column, $leagueIds->isNotEmpty() ? $leagueIds->all() : [0])`.
      Someone with no league membership at all now sees zero league-owned
      rows via a plain scoped query, XCL's own included, matching how
      `League` itself already behaved for a league-less user.
- [x] **Regression sites fixed** (found via two research passes plus a
      direct follow-up grep, then verified against real dev data through
      read-only Tinker checks — see verification notes below):
      - `RaceController::register()`/`registerTeam()`
        (`app/Http/Controllers/RaceController.php`) — `$race->load('ftpServer')`
        had no scope bypass; a driver's registration-confirmation message
        (server name/password) would have silently broken for everyone
        once XCL's servers stopped being null. Now
        `$race->load(['ftpServer' => fn ($q) => $q->withoutTenantScope()])`.
      - `RaceController::show()` — the public race page's practice-server
        "is live" banner reads `$ps->practiceServer->ftpServer->name`;
        fixed the same way via a nested eager-load constraint on
        `practiceServerSession.practiceServer.ftpServer`.
      - **Found beyond the original plan, while verifying:**
        `PushPracticeServerConfigJob::handle()`
        (`app/Jobs/PushPracticeServerConfigJob.php`) — already bypassed
        the scope for `practiceServer.ftpServer`, but
        `PracticeServerConfigService::configuration()`/`eventRules()`/`assistRules()`
        separately lazy-load `$race->ftpServer` (the race's *own* assigned
        server, a different relation) with no bypass at all — would have
        thrown once that lazy load returned null in this no-auth queued-job
        context. Fixed by adding `'race.ftpServer' => fn ($q) => $q->withoutTenantScope()`
        to the job's own eager-load call.
- [x] Fixed the other null-based queries found during exploration:
      `Admin\LeagueController::edit()`'s unassigned-servers picker (now
      `where('league_id', League::system()->id)`),
      `Admin\LeagueController::unassignServer()` (now writes
      `League::system()->id` instead of `null`), and
      `Admin\LeagueFtpServerController::index()`'s cross-league list (now
      `where('league_id', '!=', League::system()->id)`).
- [x] Re-pointed `database/seeders/PointsSchemeSeeder.php`'s `updateOrCreate`
      match key to `League::system()->id` — no seeder change needed to
      create the XCL row itself, since migrations (which create it) always
      run before seeders in any `migrate --seed` flow.
- [x] Updated the now-historical "null means XCL's own" comments in
      `database/migrations/2026_09_08_000005_add_league_id_to_ftp_servers_table.php`,
      `..._000010_add_league_fields_to_championships_table.php`, and
      `..._000011_create_points_schemes_table.php` to point at this phase's
      migration instead.
- [x] Updated `tests/Feature/LeagueTenantIsolationTest.php`'s null-bypass
      test — it now asserts the *opposite* on purpose: a plain driver's
      scoped `FtpServer::find()` returns `null` for XCL's own server too
      (only an explicit `withoutTenantScope()` read resolves it), matching
      the new design. Also fixed two count assertions
      (`test_admin_sees_every_league`, `test_console_context_without_a_user_sees_no_leagues_unless_bypassed`)
      that undercounted by one once the XCL system league exists in every
      migrated DB. Full suite: 34/35 passing (only the pre-existing,
      unrelated `ExampleTest` fails, as already documented above).
- **Verification beyond the test suite:** read-only Tinker checks against
  the real dev DB (no writes left behind) confirmed both fixed regression
  sites resolve correctly for a driver with zero league memberships —
  `RaceController`'s `ftpServer` bypass returns the real XCL server and its
  `AccServerConfigService::settings()` config, and the practice-server
  job's `race.ftpServer`/`practiceServer.ftpServer` eager loads both
  resolve. A `git grep` sweep for `->ftpServer`/`Championship::`/
  `$race->championship` across `app/` turned up no further un-bypassed
  reads reachable by a guest or league-less driver.
- **No change needed, already safe:** `PushGPortalConfigs`,
  `ImportGportalResults`, `PushPracticeServerConfigJob` already call
  `withoutTenantScope()` unconditionally (the Phase 1 fix, see the
  "Implementation note: the null tenant key" above) — they keep working
  once null becomes a real id. `Championship::pointsScheme()`'s bypass and
  the public `ChampionshipController` are unaffected (neither depends on
  null specifically). `PointsSchemePolicy`/`PointsSchemeController`
  already key off `is_template`, not null — only their comments go stale.
- **Pre-existing, unrelated gap noticed in passing, not part of this
  phase:** legacy `Admin\ChampionshipController::index()` has no league
  filtering at all — any `canManage()` admin already sees every league's
  championships mixed with XCL's own today, regardless of this migration.
  Worth a follow-up someday, not blocking.

## Phase 3 — Rounds, event generation and league scoped servers ✅ complete (2026-09-10)

- [x] Add nullable `league_id` FK to `ftp_servers`
      (`app/Models/FtpServer.php`, `database/migrations/2026_05_29_100000_create_ftp_servers_table.php`);
      apply `LeagueScope`. **Already done in Phase 1** — the task that arrived
      for Phase 1 asked for this ahead of this plan's original placement, so
      it shipped there instead (with `Tenantable`, not a separately-named
      `LeagueScope`). Nothing left to do here.
- [x] Extend the `encrypted` cast (already used for `password`,
      `app/Models/FtpServer.php:17`) to `username`/`host` for league-owned
      servers; audit `Admin\FtpServerController` and its edit view to confirm
      credentials are never echoed back into a form value, only a
      "credentials set" indicator (Decision 4). **Already done in Phase 1**
      for `username` (widened to `text`, encrypted, never echoed back —
      `resources/views/admin/servers/edit.blade.php`). `host` was left plain,
      as a judgment call: it's a connection address, not a secret, and the
      task's later, more specific Phase 1 instructions only ever called out
      "credentials" (username/password) for this treatment.
- [x] Add `game`/`platform` columns to `FtpServer`
      (`database/migrations/2026_09_10_000003_add_game_and_platform_to_ftp_servers_table.php`,
      backfilled every existing row to `platform = 'console'` — accurate,
      since `AccServerConfigService` hardcoded "Playstation 5 & Xbox Series
      S/X" into every server name up to now). Same `acc|lmu` /
      `pc|console|cross` vocabulary as `Championship.game`/`platform`
      (`SaveChampionshipStepRequest`). Added to every server create/edit
      form: XCL's own (`admin/servers/{create,edit}.blade.php`,
      `Admin\FtpServerController`), a league's own
      (`admin/leagues/edit.blade.php`, `Admin\LeagueController::storeServer()`),
      and the cross-league aggregate
      (`admin/league-servers/index.blade.php`, `Admin\LeagueFtpServerController::store()`).
      Kept `nullable` (not `required`) on `FtpServerController::update()`
      specifically — an update that omits them (an older/partial submit)
      leaves the existing value alone, same idiom already used for
      username/password on that form; a real regression here (caught by
      `LeagueAdminAccessTest`) is why this is `nullable` and not `required`
      on update, unlike `store()`.
- [x] Reuse `Race` as the round entity via the existing
      `championship_id`/`round_number` linkage; extend the cut-down
      round-create form to pre-fill from the championship's `settings` JSON
      instead of requiring re-entry per round. Session lengths, `time_of_day`
      and `ambient_temp` were already prefilled from the 2026-09-09 session;
      this pass added `rain_level` (from `settings.sessions.rain_level`) and
      defaulting the weather picker to "Random" when
      `settings.sessions.weather_mode` is `randomised` — the only two
      remaining fields with an unambiguous mapping onto the round-level form.
      **Found, not fixed — flagged as dead schema**: `settings.sessions.track_temp`/`cloud_level`
      have help text saying "only used when weather is fixed," but nothing
      anywhere (round-create form, `Race` columns, `AccServerConfigService`)
      actually reads them — there's no round-level field for either at all.
      Same category of gap as the `max_missed_rounds` finding in Phase 5;
      needs its own decision (wire them up with new `Race` columns, or drop
      them from the schema) rather than a silent fix here.
- [x] Scope the round-create FTP server picker to servers owned by the
      championship's league — the new league wizard's own
      `ChampionshipWizardController::roundCreate()` already did this
      correctly from Phase 2 (`$league->ftpServers()->where('active', true)`).
      What was actually unscoped was the **legacy** XCL-native round-create
      (`Admin\ChampionshipController::roundCreate()`,
      `FtpServer::where('active', true)` with no league filter at all) — an
      admin bypasses `TenantScope` entirely, so every league's private
      servers were showing up in XCL's own native-championship dropdown.
      Now scoped to `where('league_id', League::system()->id)`.
- [x] Extracted `App\Services\Contracts\ServerConfigGenerator`
      (`app/Services/Contracts/ServerConfigGenerator.php`) — the five methods
      the push pipeline actually calls (`entryList`, `configuration`,
      `settings`, `eventRules`, `assistRules`) plus the four `defaultX()`
      accessors the config-editor UI reads. `AccServerConfigService`
      implements it; bound in `AppServiceProvider::register()`. Every
      consumer (`PushGPortalConfigs::handle()`,
      `Admin\RaceController::show()`/`pushConfig()`,
      `Admin\FtpServerController::update()`/`pushDefaults()`,
      `RaceController::register()`/`registerTeam()`,
      `admin/servers/edit.blade.php`'s config-defaults editor) now
      type-hints/resolves the interface instead of the concrete class.
      Deliberately **not** on the interface: `bop()`/`carGroup()`
      (`Admin\BopController::pushBop()` still depends on the concrete
      `AccServerConfigService` directly) — ACC's balance-of-performance
      concept has no established LMU equivalent yet, so it isn't part of the
      generic per-race config shape this interface targets.
      `PracticeServerConfigService` also stays on the concrete class — it's
      already an ACC-specific wrapper by its own design (constructs its own
      `AccServerConfigService` as a default), not part of the generic
      pipeline.
- [x] **Decided**: XCL's own two synchronous push paths
      (`PushGPortalConfigs`, `Admin\RaceController::pushConfig`) stay
      synchronous — they're XCL-staff-operated, already working, and
      converting them now carries real regression risk for no immediate
      need. The **new** league-manager-facing push (this phase's next
      bullet) is genuinely new code with no legacy behaviour to preserve, so
      it was built as a `ShouldQueue` job from day one
      (`app/Jobs/PushRoundConfigJob.php`), following the exact pattern
      `PushPracticeServerConfigJob` already established (`tries`/`backoff`,
      a `failed()` handler posting to the same Discord webhook). This
      satisfies the "decide" bullet without a risky conversion of the
      existing paths.
- [x] Let league managers trigger manual push for their own rounds:
      `ChampionshipWizardController::pushRoundConfig()` (route
      `POST admin/leagues/{league}/championships/{championship}/rounds/{race}/push-config`)
      — checks `assertLeagueOfInterest()` + `Gate::authorize('update', ...)` +
      the race actually belongs to this championship, sets
      `config_push_status = pending`, and dispatches `PushRoundConfigJob`.
      A "Push Config" button plus a live `config_push_status` badge
      (pending/pushed/failed) now sits next to each round with an assigned
      server on the Rounds wizard step
      (`admin/leagues/championships/_rounds.blade.php`). Covered by
      `tests/Feature/ChampionshipRoundPushTest.php` (queues for your own
      round, 404s with no assigned server, 404s on another league's round,
      and the wizard page renders the button) — the queue is faked in tests,
      no real FTP connection attempted.

## Phase 4 — Registrations, teams, driver swaps and entry requirements ✅ complete (2026-09-10)

- [x] **Found while wiring this up, fixed in passing:** `ChampionshipClass` had
      no `isFull()` method at all — `ChampionshipController::register()`'s
      multiclass branch would have thrown a fatal error the first time anyone
      actually tried to register into a full class, on *any* multiclass
      championship, league or native. Added, mirroring `Championship::isFull()`.
- [x] **Found while wiring multiclass, fixed the same way:** the wizard's
      Format step wrote `settings.format.multiclass_enabled`/`classes` (JSON)
      but never touched the real `is_multiclass` column or created real
      `ChampionshipClass` rows — the two things
      `ChampionshipController::register()` actually branches on. A league
      championship's multiclass setup silently never took effect at
      registration. `ChampionshipWizardController::applyStepSettings()` now
      also sets `is_multiclass` and calls a new
      `syncChampionshipClasses()` — matched by **name**, not replace-all:
      `championship_class_id` cascades on delete
      (`2026_06_15_000003_create_championship_registrations_table.php`), so
      a naive delete-and-recreate on every save would have silently
      unregistered every driver in an untouched class. Only a class actually
      removed from the list takes its registrations with it. Covered by
      `tests/Feature/ChampionshipRegistrationTest.php`.
- [x] Source `User::requirementFailure()`'s thresholds from the championship's
      `settings.requirements` for a league-owned championship;
      `Championship::requirementThresholds()` picks the source (settings vs.
      the legacy flat `sr_requirement`/`min_rating` columns) based on whether
      `league_id` is XCL's own system league or a real one — same
      extend-don't-fork branch `buildDriverStandings()` already uses for
      points schemes. XCL's own native championships are completely
      unaffected.
- [x] Spectator slot pool: new `championship_registrations.is_spectator`
      column (`2026_09_10_000004_...`); `Championship::isFull()` now only
      counts non-spectator registrations, `spectatorSlots()`/`isSpectatorFull()`
      read `settings.format.spectator_slots` (already existed on the schema,
      never wired to anything). The public registration form
      (`resources/views/championships/show.blade.php`) shows a separate
      "Register as Spectator" action that stays available even once driver
      slots are full — a spectator isn't racing, so no SR/rating requirement
      applies to them either.
- [x] Championship-level team registration for driver-swaps-enabled
      championships: new `championship_registrations.racing_team_id`
      (same migration as `is_spectator`) — a team's **owner** registers the
      whole team in one row (same "owner registers, not any member" rule
      `RaceController::registerTeam()` already uses per-round), and
      `Championship::isRegistered()` now also recognises any member of an
      already-registered team. **Deliberately not built in this pass**: true
      roster carry-over into each round's own `RaceTeamEntry` — a team still
      registers separately at the championship level and per round for now;
      only the championship-level "don't ask individually" gate is done.
- [x] Discord-membership-as-entry-requirement, direction from the prior
      session (bot invited into each league's own guild, not a broader
      user-OAuth scope) fully built:
      - `leagues.discord_guild_id` (`2026_09_10_000005_...`), admin-only
        editable (same gate as `requires_discord_membership`).
      - `League::discordBotInviteUrl()` — a Discord OAuth2 **bot-invite**
        link, not a user-login flow. Note recorded for whoever operates
        this: the invite alone doesn't grant membership-read access — XCL's
        bot application also needs the "Server Members Intent" toggle
        enabled **once, globally**, in the Discord Developer Portal; that's
        an application-wide setting, not something a per-guild invite
        controls.
      - `DiscordRoleService::isGuildMember(guildId, discordUserId): ?bool`
        and `isBotInGuild(guildId): ?bool` — generalized the existing
        single-guild lookup; `null` (not `false`) means "couldn't check"
        (no bot token configured, connection error, unexpected response),
        kept distinct from a real negative because the two need very
        different user-facing messages. The league edit screen shows a live
        "Bot installed / not in this server / couldn't check" badge next to
        the invite link.
      - Registration enforcement in `ChampionshipController::register()`
        (`discordMembershipFailure()`): no connected Discord account → blocked
        with a "connect it first" message; guild membership confirmed →
        proceed, cached 10 minutes per user+guild so repeated attempts don't
        re-hit Discord's API; confirmed *not* a member → blocked with the
        league's invite link; verification failed (`null`) → blocked with a
        "try again in a moment" message, never silently let through. A
        league that turned the flag on but never configured a guild id
        fails open (logged as a misconfiguration) rather than blocking real
        registrations over someone else's setup gap.
      - **Real bug found and fixed while testing this**: the naive
        `$championship->league` relation resolves to `null` for the exact
        audience this check is for — a driver who isn't a member of that
        league — because `League`'s own tenant key is `id` and `TenantScope`
        scopes it same as everything else. Reads
        `$championship->league()->withoutTenantScope()->first()` instead,
        same reasoning as every other public-page league read in this file.
- [x] `ChampionshipClass` needed no league-scoping of its own — it has no
      `league_id`/`Tenantable` at all, and is implicitly scoped through its
      parent `Championship` (already tenant-scoped) via `championship_id`.
      What actually needed fixing was the wizard→real-rows sync above, not a
      missing scope.
- **Not done in this pass, explicitly deferred**: reusing
  `RaceRegistration`/`RaceTeamEntry` for round-level team entries was
  already true before this phase (rounds are just `Race` rows, unchanged);
  what's still open is the "don't re-enter per round" carry-over noted
  above.

## Phase 5 — Points schemes, drop rounds and multiclass standings

> **The points-scheme half of this phase is done (2026-09-09)**, pulled forward
> on an explicit task naming it directly — see "Points scheme system" below
> for the full rebuild (data model, generators, templates, admin, standings
> wiring). Drop-rounds is wired for scheme-based championships too (reads
> `settings.scoring.drop_rounds` when a scheme is set). What's still open is
> everything below that isn't about the scheme itself — multiclass standings
> parameterisation and the still-uninvestigated missed-rounds columns.

### Points scheme system ✅ complete (2026-09-09)

- [x] Rebuilt `points_schemes`: `league_id` (nullable = XCL template), `name`,
      `description`, `type` (manual/linear/curved), `config` (json, generator
      settings), `points_table` (json, always resolved and stored, never
      computed at read time), `fastest_lap_points`, `pole_points`,
      `leading_lap_points`, `is_template`, `scope_note`
      (`database/migrations/2026_09_09_000002_rebuild_points_schemes_table.php`,
      migrates any existing `points_map` data in place rather than dropping it).
- [x] `App\Services\PointsSchemeGenerator` — pure math, no DB access: `linear()`
      (top/gap/depth/optional floor, stops at the floor rather than going
      negative), `curved()` (top/floor/depth/named steepness preset — gentle
      1.15 / standard 1.6 / steep 2.4 exponent — via
      `points(pos) = floor + (top-floor) * ((depth-pos)/(depth-1))^exponent`,
      clamped to strictly non-increasing after rounding), `resolveDepth()`
      (fixed count or percentage against a reference field size, for building
      the *stored* table), `scoringCutoff()` (the live, per-round position
      cutoff for a percentage-depth scheme — resolved fresh each round against
      that round's own classified-finisher count, never the starting grid, so
      a retirement elsewhere never changes what a finisher scores), and
      `validateTable()`.
- [x] `PointsScheme::copyFor()` copies every field, not just the table — a
      copied scheme can still be regenerated (not only hand-edited) before its
      own championship locks it. `regenerateTable()`, `scoringCutoffFor()`,
      `championshipsInUse()`, `isLockedByCompletedRounds()` (locked once any
      championship using the scheme has a scored round — recorded overrides
      only via an audited XCL-admin action in `PointsSchemeController::update()`),
      `maxPointsPerRound()`.
- [x] `PointsSchemePolicy` — a template (`league_id` null) can never be
      updated/deleted by anyone through this policy, admin included; an owned
      scheme only by an XCL admin or that scheme's own league's manager. No
      edit/update/destroy route exists for a template id at all (route table
      + policy + tenant scope, three layers, matching the "never edit a shared
      one" requirement).
- [x] `Admin\PointsSchemeController` rewritten: `index` (owned + templates),
      `create`/`store`, `edit`/`update` (lock check + admin override), `destroy`
      (blocked while any championship still references the scheme), `copy`,
      `browse` (cross-league read-only, unchanged in spirit). CSV import/export
      removed — replaced by the generator/manual editor.
- [x] Editor (`resources/views/admin/leagues/points-schemes/form.blade.php`):
      generator controls and the resolved table side by side (CSS grid), type
      switch is CSS-only (radio + sibling selectors, no JS), live-updating
      preview and season-total-for-the-winner (given a rounds-in-season input)
      via a small hand-written JS copy of the generator math — kept in sync
      with the PHP service by construction, not by hand. Bonus points
      (fastest lap/pole/leading a lap) are on every scheme type, manual
      included.
- [x] Championship Scoring step's picker
      (`_points-scheme-picker.blade.php`) replaced: each scheme as a card
      showing its first few resolved positions inline, radio-selected, no
      separate preview click needed. (Also fixed a latent bug found while
      touching this: the step's generic field loop was *also* rendering
      `points_scheme_id` as a second, colliding plain number input alongside
      this picker — excluded it from that loop.)
- [x] Seeded templates, values verified against a live source rather than
      memory (see "Templates seeded" in the session's final report for exact
      figures + citations): Formula 1 Style, WEC Endurance Style, GT World
      Challenge Sprint, GT World Challenge Endurance, DTM Style, FIA GT World
      Cup Style (Single Event), Simple Top Three Podium, and Full Field
      Participation (the one built from the curved generator, per the task).
- [x] `Championship::buildDriverStandings()` now reads a league-owned
      championship's points from its `PointsScheme` (resolved `points_table`,
      per-round scoring cutoff, `fastest_lap_points`/`pole_points`/
      `leading_lap_points`, plus `settings.scoring.drop_rounds`) when
      `settings.scoring.points_scheme_id` is set; XCL's own native
      championships (no scheme) keep reading the legacy flat columns exactly
      as before, unchanged. `computeClassStandings()` groups the same
      `buildDriverStandings()` output, so class standings inherited this for
      free. `leading_lap_points` is awarded from `RaceResult.laps_led > 0`.
- [x] Fixed a real bug found while wiring standings:
      `Championship::pointsScheme()` used the default tenant-scoped query, so
      an unauthenticated public visitor (or anyone outside the owning league)
      got `null` back for any real league-owned scheme — silently zeroing
      public standings. Now reads via `PointsScheme::withoutTenantScope()`,
      same reasoning as `PointsSchemeController::browse()`: a scheme carries
      no credentials, so reading it isn't a leak.
- [x] Removed the dead `settings.scoring.fastest_lap_point`/`pole_point`
      booleans from `ChampionshipSettingsSchema` — confirmed unused by
      `buildDriverStandings()` even before this work; superseded by the
      scheme's own integer bonus fields, which can actually carry a value.
- **Not done, deliberately, in this pass:** an admin-authored template (the
  old placeholder let a canManage() user tick "save as template" from the
  league-scoped create form) — templates are now seeder-only. Team-points
  standings (`settings.scoring.team_points_enabled`) still doesn't compute a
  separate team classification anywhere — this pass only wired the
  *individual* points scheme into `buildDriverStandings()`.

- [ ] Investigate `max_missed_rounds`/`missed_rounds_action`/
      `missed_rounds_penalty_points` (present as columns on `championships` but
      not found wired into `buildDriverStandings()` during exploration) —
      confirm whether this is dead schema, wire it into standings math, or
      fold it into the settings schema as a supported rule.
- [ ] Verify `computeClassStandings()`'s class-grouping continues to hold once
      classes and points schemes come from per-league settings rather than
      fixed columns.
- [ ] Parameterise "Championship"-branded copy and any XCL-specific text in
      standings views (`resources/views/championships/show.blade.php`,
      `resources/views/admin/championships/show.blade.php`) so they render
      correctly for any league.

## Phase 6 — Stewarding, penalties and results adjustment 🟡 mostly complete (2026-09-10)

> This phase touches a **live, working, previously test-free** feature — the
> Report/verdict/process pipeline that already deducts real rating from real
> users today. Every change below was scoped to leave a report on a race with
> no championship (or XCL's own native championship) behaving **exactly** as
> before — new behaviour only ever activates for a real league-owned
> championship. `tests/Feature/ReportProcessingTest.php` was written from
> scratch (none existed) specifically to prove that boundary holds.

- [x] The penalty-effects setting already existed —
      `settings.penalties.affects` (`points`/`rating`/`both`/`none`,
      default `none`) shipped in Phase 2's schema, alongside
      `stewarding_enabled` and `post_race_time_penalties_enabled`. Nothing
      to add here; it had just never been consulted anywhere.
- [x] `Admin\ReportController::process()` now consults `affects` — but only
      for a **league-owned** championship (`league_id` set to a real league,
      not XCL's system league). Native championships and races with no
      championship at all are treated as `affects = both` unconditionally,
      preserving today's behaviour exactly. When `points`/`both` is in
      scope, a `ChampionshipPenalty` is created. **Judgment call, flagged for
      a league to revisit**: there's no existing formula for "how many
      championship points does a rating-scale penalty cost" — this uses the
      report's own computed `xcl_rating_deduction`, rounded, rather than an
      invented flat number, so it's at least tied to the same severity
      calculation, not arbitrary.
- [x] Rating changes now go through a new
      `RatingService::applyManualAdjustment()` — one auditable choke point
      for a non-race-result rating mutation, instead of
      `ReportController::process()` mutating `User.elo_{game}`/`sr_{game}`
      inline. Identical math/rounding to what was there before (this was a
      behaviour-preserving extraction, not a rewrite). Gated behind
      `xcl_rating_enabled` for a league-owned championship regardless of
      what `affects` says — a league manager's own setting can never turn on
      real rating changes by itself, only an XCL admin's approval
      (`Championship::approveXclRating()`, Phase 2) can. Native
      championships / no-championship reports are ungated, as before.
- [x] Post-race time penalties, built from nothing: `race_results.time_penalty_ms`
      (additive, so repeated penalties stack), a new
      `App\Services\ResultPenaltyService::recomputePositions()` that re-derives
      finishing order for a race+session from scratch (most laps completed
      wins, elapsed time — now including the penalty — only breaks a tie on
      the same lap count; a multiclass race re-ranks each class separately),
      and `Admin\RaceResultController::applyTimePenalty()` (new route, new
      "Time Penalty" column on the Results tab's Penalties table). Points/
      standings pick up the new position automatically
      (`Championship::buildDriverStandings()` reads it live) — rating is
      deliberately left alone, same as the existing DSQ/DC toggle: click
      "Recalculate Ratings" separately if the outcome should move ratings
      too, rather than this silently triggering a race-wide Elo recompute.
- [x] **Steward scoping — resolved 2026-09-10, additively**: a league's own
      designated stewards can now claim/rule on reports for that league's
      races only, *and* XCL's global `steward` role stays exactly as
      unscoped as before — both pools work at once. See "Refinements
      (batch 2)" below for the implementation.

## Phase 7 — The public league area with per league branding ✅ complete (2026-09-10)

> Most of this phase turned out to already exist from Phase 2 — but with a
> real bug that meant the public it was built for never actually saw it
> work. See the note below.

- [x] `/championships` is already a league picker
      (`ChampionshipController::index()`, no-`?league=` branch →
      `championships.leagues.blade.php`) and `?league={slug}` is already the
      league-home view (`championships.index.blade.php` — logo, colours,
      Discord link, that league's public championships). **Shipped as a
      query-string param, not a `/championships/{league}` path segment** —
      functionally identical to what this bullet asks for; left as-is rather
      than restructuring routes, since `/championships/{championship}`
      already occupies that path shape for a numeric id and a rename buys
      nothing users would notice.
- [x] **Real, previously-unnoticed bug found and fixed**: `ChampionshipController::show()`
      never eager-loaded `league` at all — the plain `$championship->league`
      relation carries `League`'s own `TenantScope`, which resolves to
      `null` for anyone who isn't a member of that specific league. That's
      every ordinary public visitor. The entire themed-page feature
      Phase 2 already built (league name/logo/colour in the hero) has
      **never actually rendered for the public** since it shipped — only
      staff (`canManage()` bypass) or that league's own manager/steward ever
      saw it work, which is exactly the audience least likely to notice it
      was broken for everyone else. Fixed with
      `$championship->load(['league' => fn ($q) => $q->withoutTenantScope(), ...])`.
      `tests/Feature/PublicChampionshipPageTest.php` asserts this as an
      actual unauthenticated guest request, not an admin one.
- [x] XCL's chrome was already intact — the show page `@extends('layouts.app')`
      like every other public page; nothing to change.
- [x] Entry requirements / rules / prizes / penalty summary, all new on the
      championship show page: two new schema fields
      (`settings.requirements.rules_text`/`prizes_text`, schema version
      bumped to 3) picked up by the wizard's generic field loop with zero
      new admin UI code; a public "Entry Requirements" card (rating/SR
      thresholds, Discord requirement, manual-approval mode) and a
      "Stewarding & Penalties" summary (`affects`, time-penalties allowed) —
      shown only for a real league-owned championship, not XCL's native one,
      since the native one doesn't use this settings system at all and would
      otherwise show a misleading wall of "None"s.
- [x] Discord gate confirmed — and **a second real gap found while
      confirming it**: two independent opt-ins existed
      (`League.requires_discord_membership`, league-wide, Phase 1; and
      `settings.requirements.discord_membership_required`, per-championship,
      already in Phase 2's schema) and Phase 4's enforcement only ever
      checked the first. A championship that opted in on its own, on a
      league that doesn't require it league-wide, silently had no
      enforcement at all. `discordMembershipFailure()` now checks either.
      The requirement is now also explained directly in the registration
      card itself (not just the league list page's banner), so it's visible
      at the actual point of action regardless of how someone reached the
      page.

---

## Open Questions

These need a human answer before the phases that depend on them can be built
correctly — they are not decisions this plan makes on its own.

- Should XCL's own native championships be modeled as `league_id = null`
  (this plan's current default), or should XCL itself become a first-class
  `League` row so nothing in the pipeline is special-cased? This materially
  affects the `LeagueScope` design in Phase 1/2.
  **Resolved (2026-09-10): XCL becomes its own `League` row.** See Phase 2.5
  above for the full migration this implies.
- What's the actual verification mechanism for "Discord membership as an entry
  requirement" — does each league install a bot/webhook XCL controls, or does
  XCL request a broader Discord OAuth scope (`guilds`/`guilds.members.read`)
  on the user's own connected account? Who does that integration work with
  each league?
  **Resolved (2026-09-10): an XCL-owned bot gets invited into each league's
  own Discord server** (not a broader OAuth scope on the user's personal
  account — someone still has to actually join that league's Discord for
  membership to mean anything). See Phase 4's Discord bullet above for the
  concrete breakdown; who does the invite legwork per league is an
  operational question, not a code one.
- Is League Manager one seat per league, or can a league have multiple
  managers with different permission levels (e.g. a league admin vs a
  league-scoped steward)? Phase 1's `league_managers` pivot assumes
  many-to-many but not tiered permissions.
  **Resolved (2026-09-10): multiple managers/stewards per league, no tiers
  needed for now.** The shipped `league_user` pivot (`role`: manager|steward
  per user per league) already is this — no schema change required. If a
  finer split is wanted later (e.g. a league-admin who can invite other
  managers vs. a plain manager who can't), that's a new tier and a
  `LeagueUserPolicy`, not requested yet.
- What happens to a league's data if the relationship with XCL ends —
  deletion, read-only archive, export to the league?
- Should XCL's global `steward` role be extended to be league-scoped, or do
  leagues need entirely separate stewards who can't see XCL's own reports (and
  vice versa)? Directly affects Phase 6.
- Can a league manager delegate/invite a helper without granting full league
  access, or is the League Manager role atomic per person for now?
- `championships.min_rounds_to_qualify` exists as a column today but wasn't
  found wired into standings logic during exploration — is it in active use,
  dead, or intended to become part of the new settings schema?
- Is there any commercial/plan-tier concept intended for leagues (free vs
  paid tier, usage limits, revenue share)? Would affect whether `League` needs
  a subscription/plan field from day one.
- Should league-submitted FTP server credentials be connection-tested before
  saving, given league managers are less trusted than internal staff and a bad
  config could silently break their own events?

---

## Refinements (post-Phase-7, 2026-09-10)

All seven phases are built (above). This is polish work on top, done on
explicit request rather than as a plan phase — kept brief here since it is
not new functionality.

- **Wizard visual pass**: the generic step renderer (Format/Scoring/
  Requirements/Penalties) now groups fields into labelled subsections inside
  a responsive `row g-3` grid (`ChampionshipSettingsSchema::sectionsForStep()`,
  `wizard.blade.php`, `_field.blade.php`), matching the race wizard's own
  grouped-subsection layout (`resources/views/admin/races/form.blade.php`)
  instead of one full-width field per line. `tests/Feature/WizardRenderSmokeTest.php`
  GETs every step to catch a Blade regression here.
- **Real duplicate found and removed**: Phase 7 had added a `rules_text`
  field for the public "Rules" section, sitting right next to the
  pre-existing `notes` field ("Additional Requirements") — both were the
  same idea ("public free text the structured fields don't cover") shipped
  twice. Merged back into `notes` (relabelled), and removed the resulting
  on-page duplicate where the public championship page had started showing
  that same text in two different cards.
- **Bulk Add Rounds**, modelled on the race wizard's Bulk Schedule
  (`admin/races/form.blade.php` / `resources/js/pages/admin/bulk-create.js`):
  a "Number of Rounds" input generates a table of rows (track + date, one
  per round), pre-filled from the championship's own recurrence settings
  via `Championship::scheduledDateTimeForRound()` — the same suggestion
  single-round Add Round already uses, just for the next N rounds instead
  of one. Session/weather/server settings are shared across the whole
  batch rather than re-asked per round, since a season normally runs one
  consistent set of conditions.
  `Admin\ChampionshipWizardController::addRound()`'s validity logic (server
  ownership, whole-hour start, slot validity/availability) was extracted
  into `resolveRoundRow()` so the new `bulkAddRounds()` enforces exactly
  the same rules per row, all-or-nothing (one bad or colliding row rejects
  the whole batch, matching `Admin\RaceController::bulkStore()`'s own
  convention) rather than creating half a season.
  **Real bug caught by its own test**: the first version numbered every
  bulk row "Round 1" — `resolveRoundRow()`'s auto-number fallback reads the
  DB's current max round, which doesn't change until the whole batch is
  actually persisted, so every row in one request saw the same stale max.
  Fixed by numbering the batch once, sequentially, before creating any of
  it. `tests/Feature/RoundCreationTest.php` covers this plus the
  all-or-nothing and per-row-validation behaviour.

## Refinements (batch 2, 2026-09-10)

A follow-up review turned up a numbered backlog of 9 items; user gave
explicit direction on each. Progress so far:

1. **Steward scoping — done.** Resolved additively, per Phase 6's own
   open question: a league's own designated stewards (`league_user`
   `role=steward`) can now claim/rule on reports for races belonging to
   their own league's championships, but XCL's global `steward` role stays
   exactly as unscoped as before — both pools work at once, never either/or.
   `User::canModerateReport()` is the single choke point: `canManageEvents()`
   or `isSteward()` (XCL's shared pool) always passes; otherwise the report's
   race's championship must belong to a league the user steward's
   (`stewardsLeague()`, already existed since Phase 1). `/admin/reports*`
   routes now also admit the `league_steward` role (previously
   `owner,admin,event_manager,steward` only); `ReportController::index()`
   additionally scopes the list itself to a pure league steward's own
   league(s); `show`/`updateStatus`/`startInvestigating`/`submitVerdict`/
   `markReady`/`dismiss` all call `canModerateReport()` and 403 otherwise.
   `process()` (owner/admin only) was untouched — league stewards were never
   meant to finalize a penalty, only investigate/verdict/dismiss.
   (`tests/Feature/ReportStewardScopingTest.php`.)
2. **`max_missed_rounds` and `track_temp`/`cloud_level` — done.**
   `ChampionshipSettingsSchema` (`CURRENT_VERSION` 5) gained `scoring.max_missed_rounds`,
   `scoring.missed_rounds_action` (none/penalise), `scoring.missed_rounds_penalty_points`,
   modelled directly on the legacy flat-column championship admin form's
   equivalent fields. `Championship::buildDriverStandings()` now computes and
   subtracts a `missed_rounds_penalty` per driver
   (`tests/Feature/MissedRoundsStandingsTest.php`). `track_temp`/`cloud_level`
   have no existing UI anywhere (including the "dailies"/regular-race forms
   this was asked to reuse — confirmed by searching for them first), so
   rather than invent new round-level fields they were wired as a
   championship-level fixed-weather fallback: when a league championship's
   `settings.sessions.weather_mode` is `fixed`,
   `AccServerConfigService::championshipFixedWeatherDefaults()` merges
   `ambient_temp`/`track_temp`/`cloud_level`/`rain_level` into the generated
   server config (`tests/Feature/ChampionshipFixedWeatherDefaultsTest.php`).
3. **Team standings — done.** `Championship::computeTeamStandings()`
   (gated on `settings.scoring.team_points_enabled`) sums each team's
   registered members' individual standings points; rendered as a "Team
   Standings" table on the public page between individual and class
   standings (`tests/Feature/TeamStandingsTest.php`).
4. **Team registration scope (per-round vs whole-championship) — done.**
   New `settings.format.team_registration_scope` (`per_round` default |
   `championship`). `championship_registrations` gained
   `car_number`/`car_model`/`starting_driver_id` (same shape as
   `race_team_entries`). When scope is `championship`,
   `ChampionshipController::register()`'s team branch collects those three
   fields once and `ChampionshipTeamEntryService::syncAllExistingRounds()`
   auto-creates a `RaceTeamEntry` + one `RaceRegistration` per eligible
   member for every existing round — no more per-round re-registration via
   `RaceController::registerTeam()`. A round added *after* such a team
   registered still gets auto-entered:
   `Admin\ChampionshipWizardController::addRound()`/`bulkAddRounds()` both
   call `syncTeamEntriesForNewRound()` right after creating the `Race`.
   `per_round` scope (the default) is unaffected — a team must still
   register per round the existing way.
   (`tests/Feature/ChampionshipRegistrationTest.php`, the four
   `test_championship_scope_*`/`test_per_round_scope_*`/`test_a_round_added_later_*`
   cases.)
5. **Discord operational setup — explicitly deferred** by the user; not
   touched this batch.
6. **`computeClassStandings()` — verified, still correct.** No test covered
   it at all before now, despite two of this session's own changes touching
   the `buildDriverStandings()` data it groups (missed-rounds penalty,
   points-scheme scoring). `tests/Feature/ClassStandingsTest.php` confirms
   grouping-by-class, omission of unclassed drivers, and that a missed-rounds
   penalty flows through into each class's own standings correctly.
7. **Hardcoded copy in the standings view — done.** The only such copy
   found on the public standings page (`championships/show.blade.php`,
   the one view rendering standings for both XCL-native and real league
   championships): a single-class championship's standings heading
   hardcoded the generic word "Championship" ("Championship Standings"),
   redundant right below the hero already naming the actual championship.
   Now reads "Overall Standings" regardless of who owns the championship.
   Everything else on that page already reads from the actual
   league/championship data (`"XCL Rating"` in the Entry Requirements card
   is a real, correctly-named platform-wide feature a league can opt a
   championship into — not a branding leak — so it was left alone).
   (`tests/Feature/PublicChampionshipPageTest.php`.)
8. **Edit Round — done.** New `GET .../rounds/{race}/edit` +
   `PUT .../rounds/{race}` (`ChampionshipWizardController::roundEdit()`/
   `updateRound()`), reusing `resolveRoundRow()`'s exact same validity rules
   Add Round already enforces. Two things `resolveRoundRow()` assumes for
   *creation* had to be corrected for *editing* an existing row: the
   slot-collision check now excludes the round's own current slot
   (`FtpServer::takenSlots($excludeRaceId)` already supported this, just
   never had a caller), and the returned `status: 'open'` is stripped
   before `$race->update()` — otherwise saving an edit on an
   already-running/finished round would silently reset it back to `open`.
   Switching (or removing) a round's server also resets its
   `config_push_status`, since carrying over a prior "pushed" status would
   misreport a config as sent to a server it never actually went to.
   The edit form itself is a standalone view
   (`round-edit.blade.php`) rather than reusing the create-only
   `_round-shared-fields.blade.php` partial, since that partial's fields
   default from the *championship's* settings (right for a new round) —
   an edit form needs to default from the *round's own* current values.
   (`tests/Feature/RoundCreationTest.php`, the six new
   `test_round_edit_*`/`test_updating_a_round_*`/`test_editing_a_round_*`
   cases.)
9. **Browser check — done, with a caveat.** No headless-browser tool
   (chromium-cli or similar) is installed on this Windows dev machine, so a
   real click-through/screenshot check wasn't possible. Instead: started
   `php artisan serve` against the real dev MySQL database (not the sqlite
   test DB) and GET the public page for every championship that actually
   exists there — all three render 200, and "Overall Standings" (item 7)
   renders correctly against real data, not just the sqlite test suite.
   Unauthenticated admin routes (`/admin/leagues`, `/admin/reports`, the
   championship wizard) correctly redirect to login (302) rather than
   fatal-erroring. `php artisan view:cache` force-compiled every Blade view
   in the app, including every one touched this session
   (`round-edit.blade.php`, `_field.blade.php`, `wizard.blade.php`,
   `show.blade.php`, `edit.blade.php`, `_rounds.blade.php`) — none failed
   to compile. Authenticated admin click-through (Add/Edit/Bulk Round,
   Reports scoping) is instead covered by this batch's 121 passing feature
   tests, which drive the real HTTP stack and assert on rendered content —
   the strongest verification available without a real browser here.
   **Unrelated observation, not touched**: the dev DB has stale test data
   from earlier work this session — a second, archived, non-system
   "XCLusive Racing" league row (id 1, `is_system=false`), and two of the
   three existing championships point at it instead of the real system
   league (id 2); one championship has no `league_id` at all. `League::system()`
   still resolves correctly (id 2, the real one) since only it has
   `is_system=true` — this isn't a live bug, just leftover dev/test rows,
   left alone since cleaning it up wasn't asked for.
