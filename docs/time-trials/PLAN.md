# Time Trials plan

## Current state (2026-09-30)

Phase one is live on production: the tables exist and the historical import has run
(10303 laps, 54 cars).

Phase two (weekly events) is built and tested but not deployed yet. Its migration is
`2026_09_30_000000_create_time_trial_events_table`.

- Admin > Events > Time Trials: create a week's event with a track, car class, window (UK
  time) and server. XCL SERVER 6 is the default. Only published events are pushed, shown and open for signup.
- `time-trials:push-due` runs every 5 minutes. Ten minutes before each hourly server restart
  it pushes the config: one practice session of the restart interval minus 5 minutes, dry,
  and the event's class as carGroup. It also pushes a forced entry list of every signup so far.
- `time-trials:collect-results` runs every 10 minutes. It reads each new `_FP.json` result
  file once and keeps each signed-up driver's best valid lap per car.
- About 90 minutes after the window closes, the same command finalizes the event. It stores
  the classification, awards points through `RatingService::applyManualAdjustment()`, and
  adds every counting lap to the All Time Records (`source = server`, `time_trial_event_id`).
- A lap counts only when it beats the driver's own all-time record in that car on the track
  (`TimeTrialStandings`).
- Points use a placeholder formula: 50 for the winner down to 1 for the last driver
  (`TimeTrialRating::points()`). The real formula replaces that one method.
- Public: `/time-trials` shows this week's event, then the All Time Records.
  `/time-trials/events/{event}` has signup, times to beat, standings and final results.

- Tables: `time_trial_cars` and `time_trial_laps` (migrations `2026_09_29_100000` / `100001`).
- Import: `php artisan time-trials:import <laps.csv> <cars.csv>` is a dry run.
  Pass `--confirm` to write. Running it again skips rows that were already imported (`source_key`).
- Public pages: `/time-trials` (track index) and `/time-trials/{track}` (leaderboard). The
  `?platform=console|pc` filter picks the board. Boards are set up in `config/time_trials.php`.

Not built yet: the real rating formula and a PC Time Trials server.

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
