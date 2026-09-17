# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

XCLusive Racing (xclusiveracing.com) — a Laravel 13 / PHP 8.3+ web app for a sim-racing esports
organization (ACC, LMU, iRacing, AC). Server-rendered Blade views + vanilla JS (Bootstrap 5, SCSS,
Vite) — no SPA framework. `README.md` and `.env.example` are stale leftovers from an early
Next.js/Supabase prototype and do not describe the current app; ignore them.

## Local environment & data safety — read before running anything

**There is no separate local/dev database.** `.env`'s `DB_HOST` points directly at the production
DigitalOcean MySQL instance (`xclusive_racing`). Any `artisan` command, Tinker session, or route
you hit while running `php artisan serve` locally reads and writes real production data. Do not
run migrations, seeders, or destructive Tinker/DB commands against it, and don't create/delete
rows as a side effect of "just testing something."

The automated test suite is safe and isolated — `phpunit.xml` forces `DB_CONNECTION=sqlite`
(`:memory:`) and `CACHE_STORE=array` for the `test` env. Prefer writing/running a test over poking
the app live whenever you need to verify behavior.

If you do need to inspect real data, treat it as read-only. When cleaning up a test row you
created live, delete by its exact ID — never by a shared attribute (title/track/date), since
other real rows can match the same value.

## Commands

```bash
composer setup      # first-time install: composer install, .env, key:generate, migrate, npm install+build
composer dev         # runs php artisan serve + queue:listen + vite dev, concurrently (this is "start the app")
npm run dev           # Vite dev server only
npm run build         # production asset build

php artisan test                          # full suite (Unit + Feature, sqlite in-memory)
php artisan test --filter=TestClassName   # single test class
php artisan test --filter=test_method_name  # single test method
vendor/bin/pint                           # code style (Laravel Pint) — run before considering PHP changes done
vendor/bin/pint --dirty                   # style-fix only changed files
php -l path/to/File.php                   # quick syntax check for a single file
```

## Architecture

### Roles & multi-tenancy

`User` roles (`app/Models/User.php`, via `roles`/`role_user`): `owner`, `admin`, `moderator`,
`event_manager`, `steward`, `broadcaster`, `league_manager`, `league_steward`,
`championship_manager`, `driver`. `canManage()` (owner/admin/event_manager) bypasses tenant
scoping entirely; everyone else is scoped to the leagues they actually belong to.

League-owned models (`Championship`, `FtpServer`, `PointsScheme`, etc.) use
`app/Models/Scopes/TenantScope.php`, which filters every query to `auth()->user()->leagueIds()`
unless the user `canManage()`. XCL itself is a real `League` row (`League::system()`), not a null
tenant — a query that must ignore tenant scoping for a public/anonymous context calls
`->withoutTenantScope()` explicitly.

**Championship is the active feature; League is being phased out** — new admin work should target
the Championship wizard/model, not the League equivalents, unless specifically asked to touch
League code.

### Races / events

Two creation paths, both converging on `App\Models\Race`:
- **Format-based**: driven by `EventFormat` (`app/Models/EventFormat.php`) — picking a format
  auto-derives title, session durations, and `event_tag` (`EventFormat::default_event_tag`).
- **Custom Race**: fields entered by hand, no `EventFormat`.

`event_tag` is *never* set by hand — it's always either derived from the row's resolved
`EventFormat` or left absent so the `races.event_tag` DB column's own default (`'daily'`) applies.
See `RaceController::deriveFormatFields()`, the single source of truth for this — don't
reintroduce a form field or CSV column for it.

Bulk race creation/import (`RaceController::bulkStore()`, `bulkImportCsv()`,
`admin/races/import-export`) shares the same per-row data shape as the day/week bulk generator, so
a CSV round-trips through the same editable preview table and submits to the same `bulkStore()`
endpoint — there isn't a second creation code path to keep in sync.

### Server / GPORTAL integration

`FtpServer` models a dedicated ACC/etc. server slot; `AccServerConfigService` generates the config
pushed to it. Config pushes and result imports run on a schedule (`routes/console.php`:
`gportal:push-configs`, `gportal:import-results`, both `everyMinute()`), backed by
`PushRoundConfigJob`. `PracticeServerSessionManager` / `PracticeWindowCalculator`
(`app/Services/PracticeServer/`) manage the separate practice-server session/window logic,
scheduled via `practice:push-due`.

### Rating

`XclRating` / `RatingService` compute the XCL-R rating adjustment per race result. A race's
effective multiplier falls back through: format multiplier → custom `xcl_r_multiplier` → legacy
`duration_key` → `1.0` — a round with neither a format nor an explicit multiplier silently lands
on `1.0`, which has caused real rating-fairness bugs before (see
`docs/championships/PLAN.md`).

### Auth

Standard Laravel auth plus Socialite (Steam, Discord, Xbox — `app/Http/Controllers/Auth/`).
`EnsurePasswordIsSet` middleware forces a password-setup step for accounts that signed up via a
social provider and never set one.

### Championships planning log

`docs/championships/PLAN.md` is a large, actively-maintained running plan + incident log for the
Championships feature. If you're working on Championships, read it first — it states its own rule
to work only on the phase marked current and to update the "Current State" section before ending
a session.

## Testing conventions

- Don't hand-write an expected HTML fragment for anything with conditional attributes (e.g. a
  `selected`/`hidden` attribute, or whitespace between adjacent `@if` blocks) — Blade's literal
  output doesn't collapse the way a naive `assertSee()` string assumes. Assert against content
  with no such variability (a `@json()`-embedded array, plain visible text), or parse the DOM with
  `DOMDocument`/`DOMXPath` instead.
- True concurrency bugs (e.g. race-registration cap races) aren't exercisable in single-threaded
  PHPUnit against SQLite — tests for these lock in the corrected control flow (the authoritative
  check now happens inside the transaction/row lock) rather than the race condition itself.
