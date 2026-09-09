<details class="xcl-event-cal mt-2 mb-3">
    <summary>+ Add to Calendar</summary>
    <div class="xcl-event-cal-menu">
        <a href="{{ $race->googleCalendarUrl() }}" target="_blank" rel="noopener">Google Calendar</a>
        <a href="{{ preg_replace('#^https?://#', 'webcal://', route('events.calendar', $race)) }}">Apple Calendar</a>
        <a href="{{ $race->outlookCalendarUrl() }}" target="_blank" rel="noopener">Outlook</a>
        <a href="{{ route('events.calendar', $race) }}" target="_blank" rel="noopener">Other (.ics)</a>
    </div>
</details>
