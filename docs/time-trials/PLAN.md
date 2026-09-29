# Time Trials plan

## Current state (2026-09-29)

Phase one is built. The data model and the historical import are ready; the import has only
been dry run. Nothing has been written to production yet.

- Tables: `time_trial_cars` and `time_trial_laps` (migrations `2026_09_29_100000` / `100001`).
- Import: `php artisan time-trials:import <laps.csv> <cars.csv>` is a dry run.
  Pass `--confirm` to write. Running it again skips rows that were already imported (`source_key`).
- Public pages: `/time-trials` (track index) and `/time-trials/{track}` (leaderboard). The
  `?platform=console|pc` filter picks the board. Boards are set up in `config/time_trials.php`.

Not built yet: signup, entry lists, FTP upload, result collection (`source = server`).

## Decisions

- Times are integer milliseconds. The `TimeTrialLap::format*()` helpers format them for display only.
- Every lap row is kept. `is_personal_best` marks each driver's fastest lap per track and car.
  If two laps are equally fast, the earlier row counts. `TimeTrialLap::recomputePersonalBests()`
  rebuilds the flag.
- Platform comes from the identifier prefix: `M` is xbox, `P` is playstation. Any other prefix is
  rejected. Console and PC are separate boards and are never merged.
- Laps are linked to members by `users.platform_id`. A linked Xbox or PSN account is also checked.
  Grid fillers are never matched. The import never creates accounts. Driver names link to
  `drivers.show` wherever a `drivers.xuid_psid` profile exists.
- Cars are keyed on the source car ID. `acc_car_model` is the console ID in `AccCarCatalog`, and it
  is only set when the catalogue's model year matches.
- Track keys are ACC's internal track names. Display names come from `Race::TRACK_IMAGE_MAP`
  through `AccServerConfigService::accTrackSlug()`.
- A sector of `999` seconds is the source sheet's placeholder for "no time" and is stored as null.
  This affects one row: source line 9392 (Flitzeflopp, Watkins Glen).
- `recorded_at` is null for imported laps. They only carry `source_event`. Ordering by date needs a
  table that maps event numbers to dates.

## Open items

- Nordschleife rows have event `0`, stored as `0`.
