# XP & Achievements — Implementation Plan

## Rule for all sessions working from this document

Read this file first, before doing anything else on this feature. Work only on the
phase marked as current in **Current State** below — never start a later phase early,
even if it looks quick or related. Tick tasks in place as they are completed. Before
ending a session, update **Current State** to say which phase is current and what was
completed last, so the next session can pick up without re-deriving context.

## Current State

- **Current phase: Phase 1, nearly complete.** The engine, the admin screens and their
  tests are built (2026-10-12) and nothing a driver can see has changed. **XP is switched
  off**: `XP_ENABLED` is not set, so no race awards anything until it is turned on. Four
  Phase 1 tasks are still open (unticked below): the stored championship classification,
  the restart/star data, late account linking, and the nightly reconcile. Do not start
  Phase 2 until those are done or deliberately moved.
- **2026-10-12 — XP is live and Phase 2 has started.** Migrations ran on production and
  `XP_ENABLED` / `XP_LAUNCH_AT` are set there. On the driver's own profile
  (`App\Xp\DriverXp`): the Driver Level card with the bar to the next level under the
  three rating columns, an Achievements tab (families as one item, progress, hidden when
  not earnable, decision 26), and the reveal of unseen XP, marked seen by
  `POST /profile/xp/seen`. The example-data preview is removed. Still open in Phase 2:
  the public driver page, entry lists, the XP leaderboard, the per-title breakdown, the
  star and restart. Note: rating and SR milestones read the driver's current value, so an
  established driver unlocks all of them at their first race after the launch.
- **2026-10-12 — open questions answered** (decisions 18 to 29). Two of the answers still ask
  for engine work that is **not built yet** (top-up and climbs). **The launch is
  2026-10-12** (decided that day): set `XP_LAUNCH_AT` and `XP_ENABLED` on production.
  1. ~~*Launch date* (decision 18).~~ **Built 2026-10-12**: `XP_LAUNCH_AT` (UK time,
     `App\Xp\Launch`). A race from before it queues no job, earns nothing and is not in
     `DriverHistory`; reports, time trials and championship entries count from it too.
     What a driver *is* (linked account, team, membership, account age) is read as it
     stands. With no launch date set nothing is awarded, even with `XP_ENABLED` on.
     The admin screens show a notice instead of an error while the tables do not exist.
  2. ~~*Two drivers on track* (decision 24).~~ **Built 2026-10-12**: a race session in
     which nobody else started earns no racing XP and is left out of `DriverHistory`.
  3. *Top-up on an improved result* (decision 19): the racing XP row is written once
     today and a re-run never adds the difference.
  4. *Climbs* (decision 28): achievements can be earned again after a restart, so an award
     belongs to a climb. `achievement_awards` is unique on driver + achievement and the
     ledger key is `achievement:{id}`; both need a climb number, and the metrics need to
     count from the restart. Change the migration before it runs on production.
- **2026-10-12 — Phase 1 build.** What exists now:
  - *Migrations* `2026_10_12_000000_create_xp_tables` (games, achievements,
    achievement_awards, xp_ledger, rank_levels, user_metrics, xp_rank_ups, xp_race_facts,
    plus `users.xp_total` / `users.xp_rank` as caches) and
    `2026_10_12_000001_seed_xp_catalogue_and_rank_curve`. **Both must run on production.**
  - *Seed*: 399 achievements, 1,239,360 XP. Title agnostic 1,010,760 XP (339 rows), title
    specific 228,600 XP (60 rows: the 49 the sheet had, plus the 11 rows of
    `nords_best_lap` / `nords_valid_streak` re-scoped to ACC, decision 17).
  - *Curve*: seeded from the sheet, then refitted in the seed migration so the title
    agnostic catalogue lands exactly on rank 150 (1,010,760 XP; the sheet had 1,071,375).
    The first four steps are untouched, rank 150 to 250 still costs 1,898,125 XP, so rank
    250 is now 2,908,885 XP. This settles question 7a.
  - *Titles*: three games (`acc`, `lmu`, `iracing`) and a platform (console / pc). Races
    already recorded both in `races.game` (`acc` = ACC console, `ac` = ACC PC); nothing
    was added to `races`. `App\Xp\Titles` does the mapping. See decision 16, revised.
  - *Code*: `app/Xp` (Titles, DriverHistory, MetricRegistry + `Metrics/`, RacingXp,
    RaceFacts, RankCurve, CatalogueImporter, XpAwarder), `App\Jobs\AwardRaceXp`,
    `config/xp.php`, commands `xp:process-race {race}` and `xp:import-catalogue`.
  - *Trigger*: `AwardRaceXp::dispatchForRace()` is called after the ratings are processed
    in `AccResultImportService::processSessions()` (scheduled importer and both manual
    imports) and in the admin's Recalculate Ratings. One queued job per linked driver.
  - *Metrics*: 94 registered, 68 countable, 26 not countable yet (listed with their
    reason in `MetricRegistry::notCountable()`).
  - *Admin*: XP & Achievements section (owner and admin): Achievements, Rank curve, Games,
    Adjustments.
  - *Tests*: `tests/Feature/Xp` (24 tests).
- **2026-10-12 — Decided (second round):** stored championship and time-trial results
  (decision 14); the star and optional restart at maximum rank (decision 15); XP keeps
  counting past the maximum (decision 11); ACC is two titles for XP with `acc`-scoped
  achievements counting both (decision 16); the two Nordschleife families marked `all`
  become title-specific (decision 17). One consequence needs a look: decision 17 makes the
  "+15 XP on one achievement" fix pointless — see question 7a.
- **2026-10-12 — Design preview (not Phase 2).** `/profile?xp_preview=1`, staff only, shows
  an example XP rank card and the animated post-race reveal with made-up data
  (`App\Services\XpPreview`, `profile/partials/xp-preview.blade.php`,
  `resources/js/components/xp-reveal.js`, the `.xp-*` styles). It reads and writes no XP.
  Phase 2 replaces the example data with the real thing and deletes `XpPreview`.
- **2026-10-12 — Plan written.** Codebase explored and this document created. No code,
  migration or config was changed for this feature.
- **2026-10-12 — Decided:** rank 250 is the maximum and is reachable without any
  title-specific (or any particular) achievement — see decision 11.
- **2026-10-12 — Catalogue read.** `xcl-xp-achievements.xlsx` is in this folder, with a
  CSV export of each sheet beside it (`achievements.csv`, `rank-curve.csv`,
  `race-xp.csv`, `design-notes.csv`). Its totals match the brief. What it changed in this
  plan is in **2.6 Catalogue check**; three open questions were answered by it and
  several new ones raised (section 6).

---

## 1. What we are building

A progression layer on top of racing at XCLusive Racing, in the spirit of Battlefield or
Call of Duty. Drivers earn **XP** from two sources — taking part in races, and unlocking
**achievements** — and their XP total places them on a **driver rank from 1 to 250**.
After a race, the next time a driver opens their profile an animated reveal shows what
they just earned; they can dismiss it. The profile also holds their full achievement
collection and their progress towards everything still locked.

### Three numbers that must never be confused

| Number | Means | Moves | Used for |
|---|---|---|---|
| **XCL Rating** | Skill | Up and down with results | Rating tiers, matchmaking limits, leaderboard |
| **Safety Rating (SR)** | Behaviour | Up with clean finishes, down with penalties | Gates which races a driver may enter |
| **XP / driver rank** | Time served | **Only ever up** | Shows how much someone has done |

XP says how much a driver has done, not how good they are. The interface must keep that
visible — if XP reads as a skill measure it devalues the XCL Rating. Concretely: XP never
appears in the same visual slot as XCL Rating, never uses the rating tier colours or
names, and is never used as an entry requirement.

### One total across every title

ACC on console, ACC on PC, Le Mans Ultimate and iRacing all feed the same XP total and
the same rank. There is no per-game XP.

Every achievement has a **game scope**:

- `all` — progress in every title adds up. A win in Le Mans Ultimate counts the same as a
  win in ACC.
- a specific title key — only progress in that title counts. This is how the
  Nordschleife family works: that circuit only exists in ACC.

Rank 150 is defined by the `all`-scope achievements alone, so a driver who only ever
races one title can still reach it. Title-specific achievements are a head start on the
climb from 150 to 250, never a gate on reaching 150. Adding a new title later therefore
does not move the curve.

### The numbers (from the brief, confirmed against the spreadsheet in 2.6)

- 399 achievements in 15 categories, supplied as a spreadsheet.
- `all`-scope achievements: **1,071,360 XP** in total. Unlocking all of them lands
  exactly on rank 150.
- Title-specific achievements: a further **168,000 XP**.
- Ranks 150 → 250 need **1.9 million XP**; after the title-specific achievements that is
  roughly **1,150 races**. This split is deliberate: if XP came only from achievements,
  the most active members would finish the system and XP would die for them. Racing XP
  is what keeps the top of the curve alive.

---

## 2. What already exists (exploration report)

### 2.1 Things to reuse

| Area | Where | Notes |
|---|---|---|
| User / profile model | `app/Models/User.php` | One `users` row per driver. Holds `elo_acc/lmu/iracing`, `sr_acc/lmu/iracing`, `platform`, `platform_id`, `legacy_races/wins/podiums`. |
| Own profile page | `ProfileController@show` → `resources/views/profile/show.blade.php` (`/profile`, auth only) | Already has a tab strip (Race History / Rating Changes, `data-tabs` + `tabs.js`). The achievement collection and the reveal belong here. |
| Public driver page | `DriverController@show` → `drivers/show.blade.php` (`/drivers/{driver}`) | Bound to the **legacy `Driver` model**, not `User`; it finds the user by `platform_id`. Showing XP publicly means bridging that. |
| Career stats | `User::raceStats()` | Races, wins, podiums, top 5/10, fastest laps — already the single source for both profile pages. Metrics should share its rules (DNS is not a start, DSQ doesn't count for wins). |
| Race results | `race_results` (`App\Models\RaceResult`) | One row per driver per session. Carries its own snapshot of the race: `race_game`, `race_track`, `race_title`, `race_scheduled_at`. |
| Result import | `AccResultImportService::processSessions()` | The one place results are written; called by the scheduled importer (`gportal:import-results`, every minute) and the two manual admin imports. Natural hook for awarding. |
| XCL Rating | `XclRating` (maths) + `RatingService::processRace()` (persistence) | Recalculated on every import and on the admin's "Recalculate Ratings". Minimum 8 drivers. |
| Safety Rating | `RatingService::applySrChange()` | Clean finish raises it; from 2 drivers on track. Penalties lower it via `applyManualAdjustment()`. |
| Rating/SR history | `App\Services\RatingHistory` | Built for the profile's Rating Changes tab. A model for a per-driver, multi-source, newest-first feed. |
| Event formats | `App\Models\EventFormat` | Per game; name, session lengths, multiplier, `default_event_tag`. |
| Championships | `App\Models\Championship`, rounds are `Race` rows | Standings are **computed live** (`computeStandings()` etc.), not stored. |
| Time trials | `TimeTrialLap` (all laps, `is_personal_best`), `TimeTrialEvent`, `TimeTrialEventLap`, `TimeTrialEventResult` (final position + points), `TimeTrialRegistration` | Finalised by `TimeTrialFinalizer`; a clear "final" moment exists here. |
| Incident reports | `App\Models\Report`, `ReportVerdict`, `PenaltyCalculator` | Reporter, reported driver, final penalty, deductions, `processed_at`. |
| Teams | `RacingTeam` (+ `racing_team_members`, `RaceTeamEntry`) | Owner, members with a role, per-race team entries. |
| Supporter membership | `Membership`, `MembershipPayment`, `User::isSupporter()` | Paid (Mollie), free (flag) or staff. |
| Inbox | `Message` | Existing per-user notifications; an alternative surface for "you unlocked…". |
| Queue | `QUEUE_CONNECTION=database`; jobs in `app/Jobs` (`ShouldQueue`, `tries`) | A worker runs in production (Discord role sync and config pushes depend on it). |
| Scheduler | `routes/console.php` | Everything `->onOneServer()`. A nightly reconcile command fits here. |
| Admin panel | `resources/views/layouts/admin.blade.php`, routes under the `admin.` prefix | Collapsible nav sections (Site, Events, Create, Esports, People, Racing, Content, Leagues). |
| Admin design tokens | `resources/scss/app.scss` | `.admin-card`, `.admin-card-header`, `.admin-form-card`, `.status-badge`, `.admin-tab-btn`; DataTables for lists; brand purple `#7c3aed` (`--xcl-purple`), dark `#111827`. Most admin styling is inline — match the surrounding page. |
| Tests | `phpunit.xml` → SQLite in memory | The only safe place to exercise this feature (see 2.4). |

### 2.2 When do race results become final? — they don't

There is no "official" or "final" flag today. What actually happens:

1. A race is `open` → `closed` at its start time → `finished` once every race session's
   results are imported (or after 24 h with none).
2. XCL Rating and SR are calculated **immediately on import**.
3. Results can change afterwards, at any time, with no cut-off:
   - re-importing a file (`updateOrCreate` on race + session + race number + player);
   - an admin toggling DSQ / DC (`RaceResultController::updateStatus`);
   - a post-race time penalty, which re-ranks the whole session (`ResultPenaltyService`);
   - adding DNS rows by hand;
   - "Recalculate Ratings";
   - a steward report processed days later, which changes rating and SR but **not**
     the result row.
4. Deleting a race keeps its result rows (`race_id` becomes null, the snapshot columns
   remain) but deletes its registrations.

Consequence for this feature: "the race is final" has to be **defined**, not read. See
the open question on corrected results.

### 2.3 Game and platform on a race — the cross-title design depends on this

- **Game: yes.** `races.game` is always set, and every result row copies it into
  `race_results.race_game`. Values in use: `acc` (ACC console), `ac` (ACC PC), `lmu`,
  `iracing`.
- **Platform: not as a column.** A race has no `platform` field. It is implied by the
  game: `acc` is the console (PS5 + Xbox cross-play) server, the other three are PC.
  Per driver, the console platform can be read from the result's `player_id` prefix
  (`M…` Xbox, `P…` PlayStation, `S…` Steam) — the convention `TimeTrialLap` already uses.
- **So "a race belongs to exactly one title and one platform" holds**, provided
  "platform" for a console race means *console*, with Xbox/PlayStation resolved per
  driver from `player_id`. The XP ledger should store both (title from the race,
  platform derived per driver) so nothing has to be re-derived later.
- **A wrinkle:** XCL Rating treats `acc` and `ac` as **one** game
  (`User::ratingGame()`, a deliberate 2026-09 decision: one shared ACC rating board). The
  brief treats them as two titles. That is workable — XP titles don't have to match
  rating boards — but it needs a decision for title-scoped achievements such as the
  Nordschleife family: does "ACC" mean console only, PC only, or both? **[blocks Phase 1]**

### 2.4 Local environment constraint

There is no local database: `.env` points at production. Migrations cannot be run or
tried locally; they run on deploy. Everything in Phase 1 must therefore be additive
(new tables, one nullable/defaulted column on `users`), reversible, and proven in the
test suite. The awarding engine must ship **switched off** behind a config flag so the
first production run is a deliberate act, not a side effect of a deploy.

### 2.5 Does the data needed to count an achievement exist today?

Honest status per kind of metric. "Exists" means a metric can be written against real
rows now.

| Metric kind | Status | Detail |
|---|---|---|
| Races started / finished | **Exists (ACC only)** | `race_results`, `session_type = race`, `dns = false`. |
| Wins, podiums, top 5, top 10 | **Exists (ACC only)** | `position`, excluding `dsq`. In a multiclass race `position` is overall unless re-ranked; class position needs `RaceResult::classifiedPositions()`. |
| Fastest laps | **Exists (ACC only)** | `fastest_lap`. |
| Pole positions / qualifying | **Exists (ACC only)** | `session_type = quali`, `position = 1`. |
| Laps completed | **Exists (ACC only)** | `lap_count`. Distance is **not** stored (no track length table). |
| Laps led | **Unreliable** | `laps_led` is only filled when the results file has a `lapsLed` key. Needs checking against real files before any achievement depends on it. |
| Clean races / incident-free | **Partly** | No per-race incident count. Proxies: SR change on the row (`sr_change > 0`), no processed report against the driver, or in-game penalties inside the stored raw JSON (`RaceSessionFile`, only for races imported since stats were kept). |
| Unique tracks | **Exists, needs normalising** | `race_track` is free text ("Spa", "Spa-Francorchamps"). ACC has a fixed list (`Race::ACC_TRACKS`); other titles have none. A canonical track key per title is required. |
| Unique cars | **Exists (ACC only)** | `vehicle` via `AccCarCatalog`, per platform. No catalogue for LMU / iRacing. |
| Event formats raced | **Exists** | via `races.event_format_id` / `event_tag`. Lost if the race is deleted (the result row doesn't snapshot it). |
| Endurance / team races | **Exists** | `races.is_endurance`, `RaceTeamEntry`. |
| Championship rounds raced | **Exists** | `races.championship_id`. |
| Championship titles won | **Not stored** | Standings are computed on the fly; there is no record of who won a completed championship. Needs a "final standings" snapshot when a championship is completed. |
| XCL Rating reached | **Exists** | Current `elo_*`, and `rating_after` per result for the history. |
| Safety Rating reached | **Exists** | Current `sr_*`, and `sr_after` per result (newer results only). |
| Time trial participation, positions, personal bests | **Exists (ACC)** | `TimeTrialEventResult`, `TimeTrialLap`. |
| Reports filed / upheld | **Exists** | `reports` (reporter, status, `processed_at`). Reports against a driver should not earn anything. |
| Team membership / ownership | **Exists** | `racing_teams`, `racing_team_members`. No join date on the pivot, so "months in a team" is not available. |
| Linked accounts (Discord, Steam…) | **Exists** | `connected_accounts`. |
| **Months as a member (account age)** | **Exists** | `users.created_at`. Only if "member" means "has an account". |
| **Supporter months** | **Partly** | Paid months can be counted from `membership_payments` (`paid_at`, live mode only). Free supporters (the `is_supporter` flag) and staff have no history at all — there is no record of *when* someone was a supporter. |
| **Referrals** | **Does not exist** | No referral code, no "referred by", nothing. |
| **Coaching sessions** | **Does not exist** | `/coaching` is a static page with a hard-coded coach list. No bookings, no sessions. |
| **Merchandise purchases** | **Does not exist** | No shop, no orders. |
| **Anything in Le Mans Ultimate or iRacing** | **Does not exist** | Races can be created for these titles, but the only results importer is ACC's (`AccResultImportService`). No result rows are ever written for LMU or iRacing, so every `all`-scope metric is in practice ACC-only today, and any LMU/iRacing-scoped achievement cannot be earned. |
| Pre-website career (legacy) | **Aggregates only** | `users.legacy_races/wins/podiums` are hand-set totals from the old site, with no per-race rows, no dates, no titles, no tracks. |

Two more data facts that shape the engine:

- **Only registered drivers get a result row**, and a row can exist with `user_id` null
  (a driver without an account, matched later). XP must be awardable when an account is
  linked after the fact.
- **Filler drivers** (`users.is_filler`) are display-only and never have results; they
  must be excluded from any XP leaderboard.

---

### 2.6 Catalogue check (spreadsheet read 2026-10-12)

The workbook has four sheets: Achievements (399 rows), Rank curve (250 rows), XP per
race (21 rules) and Design notes.

**What matches the brief**

| | Brief | Spreadsheet |
|---|---|---|
| Achievements | 399 | 399, no duplicate codes, 104 families |
| Categories | 15 | 15 |
| `all`-scope XP | 1,071,360 | 1,071,360 (350 achievements) |
| Title-specific XP | 168,000 | 168,000 — `acc` 118,800 (31), `lmu` 24,600 (9), `iracing` 24,600 (9) |
| Total | — | 1,239,360 |

**The curve.** 250 ranks; rank 1→2 costs 100 XP, rank 249→250 costs 23,650. Rank 250 sits
at **2,969,500 XP**. Rank 150→250 is 1,898,125 XP, so "1.9 million" is the *gap*, not the
total. After the 168,000 title-specific XP that leaves 1,730,125 XP from racing — about
**1,500 XP per race** over 1,150 races. Milestones noted in the sheet: rank 5 after one
clean first race, rank 10 after 3–4 races, rank 25 in a first month, rank 50 "committed
regular", rank 100 "long term member", rank 200 "years of racing".

**Racing XP is not a flat amount.** The "XP per race" sheet defines it as a sum:

- Base: 600 for finishing, 200 for entering and retiring, 25 per lap completed.
- Behaviour: 400 for a clean race (no penalties), 300 for all laps valid, 100 for
  finishing on the lead lap.
- Result: 1,000 / 700 / 500 for P1–P3, 300 for P4–5, 200 for P6–10, 100 for P11–25;
  400 for pole, 400 for fastest lap, 25 per position gained from grid to flag.
- 300 for the first race of the day; 1,000 once for a driver's first race in a new title.
- Multipliers on the whole race total: endurance ×1.5, championship round ×1.25,
  Nordschleife ×1.5, supporter ×1.1 ("Member tier ×1.15").

**Discrepancies and things the catalogue itself gets wrong**

1. **Rank 150 is 15 XP out of reach.** The curve puts rank 150 at 1,071,375 XP; the
   `all`-scope achievements sum to 1,071,360. Unlocking all of them with no racing XP
   lands on rank 149, 15 XP short. Either the curve or one achievement needs a 15 XP
   nudge — or the claim is dropped, since in practice every driver has racing XP.
2. **Two Nordschleife families are scoped `all`.** `nords_best_lap` (35,500 XP) and
   `nords_valid_streak` (25,100 XP) can only be earned in ACC but sit in the
   title-agnostic pool — 60,600 XP. The other Nordschleife rows are correctly `acc`.
3. **52,000 XP of the `all` pool requires racing several titles** — `unique_titles`,
   `unique_titles_won`, `titles_clean`, `titles_week`, `cross_platform`. A one-title
   driver cannot earn them, so "a driver who only races one title can unlock the whole
   title-agnostic pool" is not true of this catalogue. Under decision 11 this no longer
   blocks anyone from rank 250, but the sentence in the design notes should not be
   repeated to drivers.
4. **ACC is one title in the scope column but two elsewhere.** Per-title rows use `acc`
   only (no ACC PC key), while `unique_titles` goes up to 4 and `cross_platform` rewards
   console + PC. Four titles means console and PC are counted separately there.
5. **Thresholds are not all whole numbers that grow.** `safety_rating` uses decimals
   (4.5 … 9), and `nords_best_lap` uses lap times ("7:30") where *lower* is better. The
   achievement row needs a decimal threshold and a comparison direction.
6. The design notes speak of "298 achievements retroactively to 11,000 members" for the
   backfill — a size to plan Phase 3 around.

**All 94 metrics, classified against today's data**

*Computable now from existing rows (ACC only until other titles have results):*
`races_entered`, `races_completed`, `wins`, `podiums`, `top5`, `top10`, `top25`, `top50`,
`poles`, `fastest_laps`, `hat_trick`, `laps_completed`, `hours_raced` (from `total_time`),
`finish_streak`, `podium_streak`, `weeks_active`, `unique_cars`, `unique_formats`,
`unique_tracks` (after track normalising), `all_classes`, `endurance_debut`,
`endurance_races`, `multiclass_debut`, `driver_swap`, `comeback_kid` and `last_to_points`
(grid from the qualifying row), `photo_finish` (gap from `total_time`), `iron_man`
(`Race::eveningRegions()`), `xcl_rating`, `xclr_peak`, `xclr_climb_500/1000`,
`xclr_top100/top10/number_one`, `safety_rating`, `sr_max`, `redemption` (newer results
only — `sr_after` is recent), `championships_entered`, `first_report`, `reports_upheld`,
`clean_sheet`, `discord_linked`, `profile_complete`, `first_team`, `team_founder`,
`months_member` (account age), `tt_events`, `tt_pbs`, `tt_top10`, `tt_win`,
`tt_theoretical` (sector times are stored), `tt_all_tracks`, `nords_first`, `nords_races`,
`nords_laps`, `nords_endurance`, `nords_tt`, `nords_tt_top10`, `unique_titles`,
`unique_titles_won`, `titles_week`, `cross_platform`.

*Computable only from the stored raw results file (`RaceSessionFile`) — so only for races
imported since those files were kept, and only after writing a lap-level parser:*
`clean_races`, `clean_race_streak`, `valid_lap_races`, `metronome`, `nords_clean_race`,
`nords_valid_streak`, `nords_best_lap`, `titles_clean`, `lights_to_flag`, `wire_to_wire`,
`grand_slam` (the last three need laps led, which the result row does not reliably hold).
The racing-XP rules "clean race" and "all laps valid" depend on the same parser.

*Needs something small built first:*
- `championships_completed`, `championship_titles`, `champ_runner_up`,
  `champ_team_title`, `spotless_season` — 92,100 XP — need a stored final classification
  when a championship is completed (standings are computed on the fly today).
- `km_driven` — 17,600 XP — needs a track-length table per title.
- `night_shift` — needs the driver's timezone, which is optional on the profile.
- `no_contact_races` — "no reported contact" can be defined as "no upheld report against
  you in that race"; there is no contact data from the game.
- `nords_night`, `nords_wet` — the race's configured time of day and weather exist, per
  race not per lap; random weather cannot be known.
- `supporter_first`, `supporter_months` — 47,200 XP — countable for paying supporters
  only; free and staff supporters have no history.

*No system behind it at all:* `referrals` (16,700), `coaching_first`, `coaching_improve`,
`coaching_sessions` (16,800), `setup_download` (300), `livery_upload` (500), `merch`
(1,000), `member_tier` (2,000) — **37,300 XP**, all in the `all` pool. The racing-XP
"Member tier ×1.15" multiplier has no tier to apply to either.

*No data in any form:* everything scoped `lmu` or `iracing` (49,200 XP), until those
titles have a results importer.

---

## 3. Architecture

### 3.1 Data model (all new, additive)

- **`achievements`** — the catalogue. One row per achievement: stable `key`, category,
  name, description, icon, `game_scope` (`all` or a set of title keys — decision 17), `metric_key`,
  `threshold` (decimal) with a comparison direction (at least / at most — lap-time
  achievements are "at most"), `xp`, family + tier (for tiered families), sort order,
  `archived_at`.
  Rows, not code, so they can be added and edited without a deploy.
- **`achievement_awards`** — one row per driver per achievement, unique on
  (`user_id`, `achievement_id`). Stores **`xp_awarded`** as earned at that moment, the
  title it came from, the source (which race / event triggered it), `awarded_at`, and
  `seen_at` for the reveal.
- **`xp_ledger`** — append-only. One row per XP grant: user, positive `amount`, reason
  (`race`, `achievement`, `adjustment`), title, platform, source reference, and a unique
  **idempotency key**. Never updated, never deleted.
- **`xp_restarts`** — one row each time a driver at maximum rank chooses to start again:
  user, lifetime XP at that moment, timestamp. Plus a record of each time the maximum was
  reached (the stars). Neither ever touches the ledger.
- **`championship_final_results`** (and class / team equivalents) — the stored final
  classification of a completed championship (decision 14).
- **`users.xp_total`** — cached sum of the ledger, incremented in the same transaction
  as the ledger insert. Rebuildable from the ledger at any time.
- **Rank curve** — 250 cumulative thresholds in versioned config (not a table), anchored
  so that rank 150 = 1,071,360 XP. Rank is a pure function of `xp_total`.
- **`achievement_progress`** (optional cache) — last computed value per driver per metric
  per scope, so the profile can draw progress bars without recomputing every metric on
  each page view.

### 3.2 Metric registry (code)

A metric is a small class that answers one question: *what is this driver's value for
this metric, in this scope?*

```
interface Metric {
    key(): string;                 // 'race_wins'
    label(): string;               // shown in the admin dropdown
    scopes(): array;               // which scopes it can answer ('all' + title keys)
    value(User $user, string $scope): int;   // scope = 'all' or a title key
}
```

- Scope is part of the signature from day one. `all` sums across every title; a title key
  counts only that title. No title filter is bolted on later.
- For "unique" metrics (tracks, cars), `all` is the count of distinct **(title, item)**
  pairs — Spa in ACC and Spa in Le Mans Ultimate are two.
- A registry lists every metric. The admin form's metric dropdown is built from it, and
  the catalogue import rejects (or parks as inactive) any row whose metric is not
  registered. An achievement pointing at a metric nobody computes would otherwise simply
  never be awarded, silently.
- Metrics for systems that don't exist yet (referrals, coaching, merchandise, supporter
  months, LMU/iRacing) are **not registered** until the data exists. Their catalogue rows
  import as "waiting for data" and are hidden from drivers.

### 3.3 Awarding engine

- Triggered after a results import (hook next to `RatingService::processRace()` in
  `AccResultImportService::processSessions()`), and later from the other sources (time
  trial finalisation, report processing, membership payment). Runs as a queued job per
  race so a slow evaluation never holds up the importer.
- Per driver in the race, in one transaction:
  1. **Racing XP** — one ledger row with key `race:{race}:{race_number}:{user}`. The
     amount is the sum of the "XP per race" rules (2.6) and the row keeps the breakdown
     (base, laps, behaviour, position, bonuses, multipliers) so the reveal can show where
     it came from. The rules live in config, the awarded amount on the row.
  2. **Achievements** — for each active achievement the driver hasn't earned whose metric
     could have moved, compare `value(metric, scope)` with the threshold; if met, insert
     the award (protected by the unique index) and its ledger row
     (`achievement:{achievement}:{user}`), copying the catalogue's XP onto both.
- **Idempotent by construction**: both inserts hit unique keys, so processing the same
  race twice — a re-import, a recalculation, a retried job — changes nothing.
- **Never subtracts.** If a metric later drops (a DSQ removes a win, SR falls below a
  threshold), the award and its XP stay. See the open question on corrected results.
- A nightly reconcile command re-evaluates recently active drivers to catch anything a
  failed job missed. Safe because of idempotency.
- A config flag (`xp.enabled`) keeps the whole engine inert until launch.

### 3.4 Reads

- **Rank after a restart** (decision 15) = curve lookup on
  `xp_total − xp_at_last_restart`. Restarts are rows in `xp_restarts` (user, lifetime XP
  at that moment, when); stars are counted from the times the maximum was reached.
- **Rank** = curve lookup on `xp_total`. It cannot disagree with the XP held.
- **Per-title breakdown** = `GROUP BY game` on the ledger. No recomputation.
- **History** = the ledger and the award rows as written. Editing the catalogue changes
  what is still to earn, never what was earned.

---

## 4. Decisions and their reasoning

1. **XP is append-only. A driver's total can never fall.** This is the promise the whole
   system makes; XP is "time served", and time served cannot be un-served. Every rule
   below exists to protect it.
2. **XP is stored on the award row at the moment of awarding**, never looked up from the
   catalogue at read time. History is immutable, and the catalogue can be rebalanced
   freely afterwards without rewriting anyone's past.
3. **Achievements are database rows; metrics are code.** Rows can be added and edited
   without a deploy. The admin can only pick a metric that exists in the registry,
   because an achievement pointing at an uncomputed metric would silently never be
   awarded.
4. **Achievements are archived, never deleted.** A driver who earned one keeps it and its
   XP. Archiving only removes it from the list of things still to earn.
5. **Awarding is idempotent.** Processing the same race twice must never award the same
   achievement or the same racing XP twice. Enforced by unique database keys, not by
   "check then insert" in code — results here are re-imported and recalculated routinely
   (see 2.2).
6. **Rank is derived from total XP through the curve**, so it can never disagree with the
   XP a driver holds. Rank is never stored.
7. **Metrics are computed per title and in total.** Asked for `all`, a metric sums across
   every title; asked for a title, it counts only that one. This is in the metric
   signature from the start.
8. **A race belongs to exactly one title and one platform.** Every ledger row and every
   award records the title it came from, so a per-title breakdown needs no recomputation.
9. **A circuit that exists in more than one title counts separately per title** for the
   unique-track achievements, because driving Spa in ACC and in Le Mans Ultimate is a
   different experience. The same applies to cars.
10. **Rank 150 depends on `all`-scope achievements only.** Title-specific achievements
    are a head start between 150 and 250, never a gate, so a one-title driver can reach
    150 and a new title never moves the curve.

11. **Rank 250 is the maximum, and no achievement is a gate on reaching it** (decided
    2026-10-12). A driver with thirty iRacing achievements still open — or every
    achievement of another title untouched — who has done everything in ACC must still be
    able to reach 250. Racing in more titles helps, it is never required. Max is max.
    What this means for the design:
    - Racing XP is the uncapped source that guarantees it: whatever a driver has not
      unlocked can always be made up by racing. Achievement XP only shortens the climb.
    - The curve's top (rank 250) is a fixed XP threshold, not "everything unlocked".
      Nothing in the engine may compute a rank from "achievements completed".
    - The rank stops at 250, but **XP keeps counting past it** (confirmed 2026-10-12): a
      driver may earn more XP than the curve has ranks for, and may get to the maximum
      faster than the curve was designed around. Nothing caps or discards XP.

14. **Championship results and time-trial event results are stored for good** (decided
    2026-10-12), so they can be looked back on later — not only so achievements can be
    counted.
    - *Time trials* already do this: when an event is finalised its classification is
      written to `time_trial_event_results` (position, lap time, car, points). Nothing
      new to store; what is missing is a place to browse past events' results.
    - *Championships* do not: standings are computed live from the result rows every time
      they are shown, so a completed championship's outcome is not a stored fact and would
      silently change if an old result row were edited. When a championship is completed
      its final classification — drivers, classes, teams, positions, points — is written
      once to its own tables and shown from there afterwards.
    - The championship achievements (titles, runner-up, team title, seasons completed,
      spotless season — 92,100 XP) are counted from that stored classification, never
      from a live recomputation.
15. **Maximum rank earns a star, and the driver may then choose to start again**
    (decided 2026-10-12).
    - Reaching the maximum gives a **star shown under the rank**. It is permanent.
    - The driver may then **choose** to restart from rank 1. It is never automatic. The
      star stays as the mark of having reached the maximum.
    - **This does not break "XP never falls".** The ledger and the lifetime XP total are
      untouched by a restart. What restarts is the *rank*: the driver gets a restart
      marker holding their XP total at that moment, and rank is derived from the XP earned
      since (`xp_total − xp_at_last_restart`). Lifetime XP stays visible on the profile.
    - Rank is therefore derived from two stored facts — lifetime XP and the last restart
      marker — and still can never disagree with them (decision 6).
    - The details are in decision 28.
16. **For XP, ACC is one title on two platforms** (revised 2026-10-12 by the Phase 1
    brief; it replaces the "four title keys" answer of earlier that day). The `games`
    table holds `acc`, `lmu` and `iracing`; a race also has a platform, console or PC.
    `races.game` already carries both: `acc` is ACC on console, `ac` is ACC on PC, and
    `App\Xp\Titles` maps them to (`acc`, console) and (`acc`, pc). What follows:
    - an ACC-scoped achievement counts both platforms, as decided;
    - `cross_platform` rewards racing on console and on PC;
    - `unique_titles` and `unique_titles_won` count three titles today, so their tier 4
      rows cannot be reached until a fourth title exists;
    - the one-off "first race in a new title" bonus is once for ACC, not once per platform;
    - a circuit or car counts once per title, so Spa on ACC console and ACC PC is one.
    XCL Rating already treats the two as one board, so the systems now agree.
17. **A circuit-specific family is scoped to the titles that have the circuit, never
    `all`** (decided 2026-10-12). `nords_best_lap` and `nords_valid_streak` move from
    `all` to ACC. The Nordschleife also exists in iRacing, and Le Mans Ultimate has
    circuits of its own (Le Mans), so:
    - as built, an achievement's scope is `all` or **one** title key (the Phase 1 brief's
      schema). The Nordschleife families are `acc`. When iRacing has results, its
      Nordschleife ladder is added as its own rows scoped `iracing`, or the scope column
      is widened to a set then;
    - title-specific achievements for a title are added to the catalogue when that title
      starts (LMU's own ladder when LMU launches), not before. Until then nothing scoped
      to a title without results is shown to drivers.
18. **Everybody starts at zero on launch day** (decided 2026-10-12). Races driven before
    the launch date earn no XP and count towards no achievement, and the pre-website
    totals (`users.legacy_*`) count for nothing. There is no backfill and no welcome
    screen. The launch date is one stored value; every race metric counts from it. Facts
    that are not races are read as they stand: current Safety Rating and XCL Rating,
    months since the account was created, a linked Discord account.
19. **Awards are made straight after the race and never taken back** (decided
    2026-10-12). No grace period. A later penalty or DSQ removes nothing (decision 1). If
    a correction makes the result better, the difference in racing XP is added as a
    top-up row, and achievements the corrected result now meets are awarded.
20. **XP, level and achievements are public** (decided 2026-10-12): on the public driver
    page, next to the name in entry lists and results, and on an XP leaderboard. Filler
    accounts are left out of the leaderboard.
21. **Every race of a multi-race round earns racing XP on its own, and every driver of a
    shared car earns the full amount** (decided 2026-10-12). Both are as built.
22. **Supporters earn 10 percent more racing XP** (decided 2026-10-12), read from the
    driver's status when the race is processed. As built.
23. **"First race of the day" uses the site's day** (UK time), the same for every driver
    (decided 2026-10-12). As built.
24. **A race counts from two drivers on track** (decided 2026-10-12), the same floor as
    Safety Rating. A driver alone in a lobby earns no racing XP and no progress. XCL's own
    events and league championship rounds both count.
25. **On screen the XP rank is called "Driver Level"** (decided 2026-10-12): "Level 27".
    "Rank" keeps meaning the XCL Rating tier (Rookie to Legend), on the site and in
    Discord. In code it stays `xp_rank` / `RankCurve`; only copy says Level. This plan's
    "rank 150" and "rank 250" read as Level 150 and Level 250.
26. **An achievement that cannot be earned yet is hidden from drivers** (decided
    2026-10-12): the ones scoped to a title without results (LMU, iRacing) and the ones on
    a metric that is not countable. They appear by themselves once they can be earned.
    The admin list keeps showing them, marked.
27. **"Months as a member" is months since the account was created** (decided
    2026-10-12). As built.
28. **Restarting at the maximum** (decided 2026-10-12, completes decision 15):
    - the star is given on reaching the maximum, and one more for every completed climb;
    - a restart is confirmed once and cannot be undone;
    - achievements earned stay shown as earned, for good;
    - **in the new climb every achievement can be earned again**, its counters starting
      from the restart, and pays XP again. A second star is meant to be reachable.
    - So an award belongs to a climb: one award per driver, achievement and climb.
29. **Results a driver raced before linking an account count once linked**, back to the
    launch date and no further (decided 2026-10-12).

Additional decisions proposed by this plan (to confirm):

12. **The curve is frozen at launch.** Rank is derived, so editing the curve later could
    lower a driver's displayed rank even though their XP never fell. Treat the curve as
    immutable once live; extend it, never reshape it.
13. **The engine ships switched off** (see 2.4), and Phase 1 has no user-facing output.

---

## 5. Phases

### Phase 1 — Data model, metric registry, catalogue import, awarding engine, admin screens

No user-facing display. Nothing a driver can see changes in this phase.

- [x] Resolve every open question marked **[blocks Phase 1]** (answered 2026-10-12).
- [x] Add the catalogue spreadsheet and the rank curve to the repository.
- [x] Check the spreadsheet against the brief: 399 rows, 15 categories, `all`-scope sum
      1,071,360 XP, title-specific sum 168,000 XP. Differences recorded in 2.6.
- [x] List every distinct metric the catalogue uses and classify each against 2.5:
      computable now / needs normalising / waiting for data. (94 metrics, in 2.6.)
- [x] Lap-level facts from the stored raw results files: no in-game penalty, every lap
      valid (`App\Xp\RaceFacts`, `xp_race_facts`). Laps led is **not** read yet, so
      `lights_to_flag`, `wire_to_wire`, `grand_slam` stay not countable.
- [ ] Stored final classification when a championship is completed — drivers, classes,
      teams (decision 14) — plus a one-off command to store it for championships that are
      already completed. Until then the five championship metrics are not countable.
- [x] Catalogue fixes at import: `nords_best_lap` and `nords_valid_streak` scoped to ACC
      (decision 17). Scope is a single key, see decision 17.
- [ ] `xp_restarts` and the maximum-reached record (decision 15) — data only in this
      phase; the star and the restart button are Phase 2.
- [ ] Track-length table (for `km_driven`).
- [x] Config: `config/xp.php` — enabled flag and the racing-XP rules. The curve is a
      table (`rank_levels`), not config.
- [x] Migrations (additive only), see Current State.
- [x] Models with the append-only rule enforced on the ledger (`XpLedgerEntry` refuses
      an update of anything but `seen_at`, and any delete).
- [x] Rank curve class (`App\Xp\RankCurve`): XP to rank, health, refit preview and refit.
- [x] Canonical track keys (`Titles::trackKey()`), so "Spa" and "Spa-Francorchamps" are
      one circuit. Cars use the catalogue name as stored on the result.
- [x] Metric base class, the registry, and every countable catalogue metric.
- [x] Catalogue import command (`xp:import-catalogue`): upsert by code; metrics that are
      unknown or not countable are reported, and can never award.
- [x] Awarding engine: racing XP + achievement evaluation in one transaction with a lock
      on the driver, unique keys, one queued job per driver per race.
- [x] Hook the engine into `AccResultImportService::processSessions()` behind the flag.
- [ ] Late account linking: when a result row gains a `user_id`, the driver is evaluated.
      (Today: run `xp:process-race {race}` for the races involved.)
- [ ] Nightly reconcile command in `routes/console.php`.
- [x] Tests: see `tests/Feature/Xp`. A DSQ after the fact removing nothing is covered by
      the ledger being append only and the race key being unique; there is no dedicated
      test for it yet.
- [x] Admin › "XP & Achievements" nav section:
  - [x] Achievements list (filter by category, scope, family, archived) with create /
        edit / archive, holders per achievement, metric from the registry only.
  - [x] Curve health panel and the refit with a preview.
  - [x] Games screen.
  - [ ] Catalogue import screen with a dry-run report (the command exists; no screen).
  - [ ] Per-driver view: XP total, rank, ledger, awards.
  - [x] Manual XP adjustment (positive only), written to the ledger with a reason.
  - [ ] Health view: achievements waiting for data, last engine run, failed jobs. (The
        list marks an achievement on a metric that is not countable or not registered.)
### Phase 2 — Profile page, collection, progress, animated reveal

- [x] Public visibility: settled, everything is public (decision 20).
- [ ] Profile header: driver rank and XP bar, visually separate from XCL Rating and SR
      (own slot, own colour, labelled as activity — not skill).
- [ ] Achievement collection on the profile (a new tab next to Race History / Rating
      Changes): earned and locked, by category, with the title each was earned in.
- [ ] Progress towards locked achievements, from `achievement_progress`.
- [ ] Tiered families shown as one item with its tiers, not N separate cards.
- [ ] Per-title XP breakdown.
- [ ] Star under the rank for a driver who has reached the maximum, and the opt-in
      "start again" action with a clear confirmation (decision 15). Lifetime XP stays shown.
- Moved out: the pages to look back at completed championships and past time-trial
  events are their own task after Phase 2 (answered 2026-10-12). Phase 1 only stores.
- [ ] Animated reveal on opening the profile when there are unseen awards: XP gained,
      rank-ups, achievements unlocked; dismissible; marks awards as seen; respects
      `prefers-reduced-motion`; never blocks the page.
- [ ] Phone layout for all of the above.
- [ ] Public surfaces: public driver page, entry lists and results, XP leaderboard
      without filler accounts (decision 20).
- [ ] Hide what cannot be earned yet (decision 26). The label is "Driver Level"
      (decision 25).
- [ ] Tests for the collection, the progress numbers, and seen/unseen state.

### Phase 3 — Launch

There is no backfill: everybody starts at zero on launch day (decision 18).

- [ ] The four engine changes listed in Current State (launch date, two-driver floor,
      top-up, climbs).
- [ ] Dry run on production data from a chosen date: XP and level distribution after a
      normal week, reviewed before anything is written.
- [ ] Set the launch date, switch the engine on (`xp.enabled`), announce, and watch the
      first live weekend.
- [ ] Runbook: how to re-run a race, how to grant a manual adjustment, what to do if a
      race is imported against the wrong event.
---

## 6. Open questions

Everything asked up to 2026-10-12 is answered; the answers are decisions 11 and 14 to 29.
For the record, where each question went:

| Question | Answer |
| --- | --- |
| Do historical races count? | No, decision 18 |
| Results corrected by the stewards | Decision 19 |
| Public or private | Public, decision 20 |
| Multi-race rounds, co-drivers | Decision 21 |
| ACC one title or two, the rank 150 gap, the Nordschleife scope | Decisions 16 and 17, Current State |
| Supporters' 10 percent | Decision 22 |
| Whose day | Decision 23 |
| Lap-level parser in Phase 1 | Built (penalties and valid laps); laps led is not read |
| The word "rank" | Driver Level, decision 25 |
| Titles and systems that do not exist yet | Hidden, decision 26 |
| Months as a member | Decision 27 |
| Which races count | Decision 24 |
| A metric that goes down again (SR 7.00) | Awarded when true and kept, follows from decision 1 |
| Results not linked to an account | Decision 29 |
| Who may edit the catalogue and grant XP | Owner and admin, as built |
| Restarting at the maximum | Decision 28 |
| Pages for stored results | Own task after Phase 2 |

Still open:

1. **Who runs the two migrations on production**, and when. They are additive, but they
   run against the live database (2.4). The climb number of decision 28 has to be in the
   migration before that.
2. **The launch date** itself.
3. **A level for a climb after the first**: the curve is the same one (decision 28 makes
   achievements earnable again so it is reachable). Confirm when the restart is built.