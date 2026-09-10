# Championships Platform — Implementation Plan

## Rule for all sessions working from this document

Read this file first, before doing anything else on this feature. Work only on the
phase marked as current in **Current State** below — do not start a later phase
early, even if it looks quick or related. As tasks are completed, tick their
checkboxes in place. Before ending a session, update **Current State** to say
which phase is current and what was last completed, so the next session can pick
up without re-deriving context.

## Current State

- **Phase in progress:** none — **Phase 2.5 ("XCL becomes a first-class
  League")** is next and has not been started; it was inserted ahead of
  Phase 3 on 2026-09-10 once the three Open Questions below were resolved
  (see their "Resolved" notes) — decision 1 there reopens part of what
  Phase 1/2 already shipped, so it should land before Phase 3 builds
  further on the tenant model. Phase 3 ("Rounds, event generation and
  league scoped servers") remains next after that and has not been
  started. The points-scheme slice of Phase 5 was pulled forward and
  completed out of order on 2026-09-09, on an explicit, self-contained
  task naming it directly — see that phase's own entry below for what
  shipped and what's still open there. Phase 3/4 themselves are untouched.
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

## Phase 2.5 — XCL becomes a first-class League

> Inserted 2026-09-10, after resolving the "should XCL itself become a
> League row" Open Question below. `TenantScope` currently treats
> `league_id IS NULL` as "belongs to XCL, visible to everyone"
> (`app/Models/Scopes/TenantScope.php:23-34`) — this phase replaces that
> null-bypass with a real, seeded XCL `League` row, so nothing in the
> pipeline is special-cased on null any more. Do this before continuing
> Phase 3 — Phase 3 extends league-scoping on `FtpServer`/rounds, which is
> simpler to build once the tenant model is final.

- [ ] Seed an "XCLusive Racing" `League` row. Decide how it's protected
      from accidental deletion (a new `is_system` boolean, or a policy
      guard blocking archive/delete on a known id) — needed before the
      backfill below, since `points_schemes.league_id` uses
      `cascadeOnDelete()` (`database/migrations/2026_09_08_000011_create_points_schemes_table.php:13-14`),
      unlike `ftp_servers`/`championships` (`nullOnDelete()`). That's inert
      today because `NULL` never cascades — once XCL templates point at a
      real, deletable `League` row, deleting it would cascade-delete every
      seeded template. Fix by switching this FK to `nullOnDelete()`, or by
      relying on the system-league delete guard.
- [ ] Backfill migration: every `league_id IS NULL` row on `ftp_servers`,
      `championships`, `points_schemes` → XCL's real league id.
- [ ] Remove `TenantScope`'s `whereNull($column)` branch
      (`app/Models/Scopes/TenantScope.php:29`) now that nothing is
      genuinely null for "belongs to XCL" — keep the column nullable for a
      true future "unowned" edge case, just stop treating null as meaning
      XCL.
- [ ] **Highest-risk regression, fix explicitly:**
      `RaceController::register()`/`registerTeam()`
      (`app/Http/Controllers/RaceController.php:155,175,272,302`) load
      `$race->ftpServer` with no `withoutTenantScope()` and no null-check —
      they only work today because of the null-bypass (an ordinary driver,
      member of no league, still needs to see XCL's own server to get its
      connection details in their registration-confirmation message). Once
      XCL's servers carry a real `league_id`, this relation load must
      bypass the tenant scope explicitly, the same way
      `Championship::pointsScheme()` already does for its own public-read
      case.
- [ ] Fix the other null-based queries found during exploration:
      `Admin\LeagueController::edit()` (`app/Http/Controllers/Admin/LeagueController.php:90`,
      `FtpServer::withoutTenantScope()->whereNull('league_id')` → `where('league_id', $xclLeagueId)`),
      `Admin\LeagueController::unassignServer()` (`app/Http/Controllers/Admin/LeagueController.php:215`,
      writes `league_id => null` to mean "back to XCL" → write the real id
      instead), and `Admin\LeagueFtpServerController::index()`
      (`app/Http/Controllers/Admin/LeagueFtpServerController.php:20`,
      `whereNotNull('league_id')` → `where('league_id', '!=', $xclLeagueId)`).
- [ ] Re-point `database/seeders/PointsSchemeSeeder.php`'s `updateOrCreate`
      match key (currently `league_id => null`, lines ~150/169) to the
      XCL league id, and sequence it **after** a new seeding step creates
      the XCL league row — `LeagueSeeder`/`DatabaseSeeder` currently seed
      NLRL/SRC/EER only, nothing seeds "XCL" itself.
- [ ] Update the now-stale "null means XCL's own" comments in
      `database/migrations/2026_09_08_000005_add_league_id_to_ftp_servers_table.php`,
      `..._000010_add_league_fields_to_championships_table.php`, and
      `..._000011_create_points_schemes_table.php`.
- [ ] Update `tests/Feature/LeagueTenantIsolationTest.php`'s "plain driver
      still sees XCL's own null-league FTP server" test to use the real
      XCL league instead of `league_id => null`; re-run the whole file
      before/after to confirm isolation still holds once the null-bypass
      is gone.
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

## Phase 3 — Rounds, event generation and league scoped servers

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
- [ ] Add `game`/`platform` columns to `FtpServer` — today the console
      assumption is hardcoded in `AccServerConfigService` defaults, not
      schema — so a league server can declare console vs PC and, later, LMU.
- [ ] Reuse `Race` as the round entity via the existing
      `championship_id`/`round_number` linkage; extend the cut-down
      round-create form (`AdminChampionshipController::roundCreate`/`addRound`,
      `resources/views/admin/championships/round-create.blade.php`) to
      pre-fill from the championship's `settings` JSON instead of requiring
      re-entry per round. **Partially done already**: the 2026-09-09
      out-of-order session added `Championship::scheduledDateTimeForRound()`
      and `ChampionshipWizardController::roundCreate()` already computes a
      `$suggestedScheduledAt` from it — verify what's still missing
      (session length/weather defaults into the round-create form) rather
      than treating this bullet as untouched.
- [ ] Scope the round-create FTP server picker
      (`FtpServer::where('active', true)`, `Admin/ChampionshipController.php:173`)
      to servers owned by the championship's league (or XCL's own, for native
      championships).
- [ ] Extract a `ServerConfigGenerator` interface that `AccServerConfigService`
      implements, so a future `LmuServerConfigService` can be added without
      touching the push pipeline — do this refactor now, while there is only
      one implementation, per the brief's "must not assume ACC everywhere."
- [ ] Audit the two synchronous push paths
      (`app/Console/Commands/PushGPortalConfigs.php`,
      `Admin\RaceController::pushConfig` line 903) against the queued
      practice-server push (`app/Jobs/PushPracticeServerConfigJob.php`);
      decide whether league-triggered pushes — running far more often, across
      many more servers, operated by less-trusted users — should be converted
      to a `ShouldQueue` job for retry/reliability.
- [ ] Let league managers trigger manual push / adjust-and-push for their own
      rounds only, reusing the existing push UI/action gated by league scope.

## Phase 4 — Registrations, teams, driver swaps and entry requirements

- [ ] Reuse `RaceRegistration` (per-round individual signup) and
      `RaceTeamEntry`/`RacingTeam` (team entry + driver-swap roster,
      `app/Models/RaceTeamEntry.php`, `app/Models/RacingTeam.php`) for
      round-level entries — these already model owner/members, car
      number/model, starting driver, and swap roster.
- [ ] Extend the championship-level registration concept
      (`app/Models/ChampionshipRegistration.php`, currently individual-only)
      to optionally reference a `RacingTeam`/team entry for championships
      where driver swaps are enabled, so a team doesn't have to re-enter per
      round.
- [ ] Source `User::requirementFailure()`'s
      (`app/Models/User.php:46`) thresholds from the championship's `settings`
      schema (Phase 2) instead of the ad hoc `sr_requirement`/`min_rating`/
      `max_rating` columns used today.
- [ ] Extend `Championship::isFull()`/registration logic
      (`app/Models/Championship.php:85-96`) to track a separate spectator-slot
      pool alongside `max_drivers`.
- [ ] Design and build Discord-membership-as-entry-requirement — genuinely new;
      no per-league guild-membership check exists (`SyncDiscordRankRole` only
      syncs XCL's own single guild). **Direction decided 2026-09-10** (see
      the resolved Open Question below): an XCL-owned bot gets invited into
      each league's own Discord server, rather than requesting a broader
      OAuth scope on each user's personally-connected account. Concretely:
      - `leagues` needs a new `discord_guild_id` column — it was in this
        plan's original Phase 1 sketch but was dropped from what actually
        shipped; only `discord_invite_url` and the currently-cosmetic
        `requires_discord_membership` flag exist today
        (`resources/views/championships/index.blade.php:37-41` only
        renders a warning string, nothing blocks registration yet).
      - A bot-invite flow: a Discord OAuth2 **bot-invite** URL (not a
        user-OAuth flow) pre-filled with the league's guild id and
        `View Server Members` permission, surfaced on the league edit
        screen, plus a lightweight "is the bot actually in this guild yet"
        check (`GET /guilds/{id}` with the bot token; 403/404 = not
        installed).
      - Generalize `App\Services\DiscordRoleService`'s member-lookup call
        (`GET /guilds/{guild}/members/{user}`, 404 = not a member — the
        exact call already exists) to take an arbitrary guild id instead of
        reading `config('services.discord.guild_id')`.
      - Wire the check into registration with a short-TTL cache per
        user+guild, so a registration attempt doesn't hit Discord's API on
        every load — same pattern as `User::requirementFailure()`'s other
        threshold checks (the "source thresholds from settings" bullet
        above).
      - Reusable as-is: `ConnectedAccount`/`User::connectedAccount('discord')`
        for the user's Discord identity (snowflake only — Socialite never
        keeps an OAuth token around, confirming a per-user API approach was
        never viable anyway), and the existing `ShouldQueue` + database
        queue setup.
- [ ] League-scope the existing `ChampionshipClass`/class-picker pattern
      (`app/Models/ChampionshipClass.php`,
      `AdminChampionshipController::syncClasses`) — no new model needed for
      multiclass entry.

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

## Phase 6 — Stewarding, penalties and results adjustment

- [ ] Add a per-championship penalty-effects setting to the settings schema:
      whether a steward-issued penalty affects championship points, XCL
      Rating, both, or neither.
- [ ] Wire `Admin\ReportController::process()` (line 276) and/or
      `PenaltyCalculator` (`app/Services/PenaltyCalculator.php`) to consult
      this setting; when points are in scope, create/update a
      `ChampionshipPenalty` row instead of, or alongside, the current direct
      rating-column mutation.
- [ ] Route steward-issued rating changes through `RatingService`/`XclRating`
      (`app/Services/RatingService.php`, `app/Services/XclRating.php`) instead
      of `ReportController`'s direct `User.elo_{game}` mutation, so rating
      changes stay auditable in one place — and gate this entirely behind the
      championship's `xcl_rating_eligible` flag (Phase 2), never applying a
      rating change for a non-eligible championship even if the league's
      steward system is otherwise active.
- [ ] Build "time penalties applied to results after a race": currently no
      automatic position/time recalculation exists anywhere — this needs new
      logic to apply a time penalty to a `RaceResult` and recompute affected
      finishing positions and the points that flow from them.
- [ ] Decide and implement steward scoping: whether a league's own designated
      stewards (vs XCL's global `steward` role,
      `User::isSteward()`/`app/Models/User.php:109`) can claim/rule on reports
      for that league's races only. Today `steward` is a single global,
      unscoped role.

## Phase 7 — The public league area with per league branding

- [ ] Turn `/championships` into a league picker
      (`ChampionshipController::index`,
      `app/Http/Controllers/ChampionshipController.php`) — list active
      `League`s, not a flat championship list.
- [ ] Add `/championships/{league}`: league home showing logo, colour scheme,
      Discord link, and that league's championships — reuse the visual
      language of `resources/views/championships/index.blade.php`, themed via
      CSS custom properties sourced from `League` fields rather than the
      hardcoded colours currently in the blade templates.
- [ ] Extend `/championships/{league}/{championship}` from the existing
      `championships.show` route/view
      (`resources/views/championships/show.blade.php`) with the league's
      branding wrapper, keeping its existing standings/rounds/registration
      content.
- [ ] Keep XCL's own persistent site chrome/nav around the themed content area
      — it should read as the league's home while staying clearly on the
      XCLusive platform, not a full white-label takeover.
- [ ] Surface entry requirements, league rules text, penalty-system summary,
      and prizes on the championship show page — league "rules" as
      free/rich text is a new field, not present today; the rest come from the
      Phase 2 settings schema.
- [ ] Confirm the Discord entry-requirement gate (Phase 4) is enforced and
      explained at the point of registration on this page.

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
