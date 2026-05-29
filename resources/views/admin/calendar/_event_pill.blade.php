<a href="{{ $race->isPast() ? route('admin.races.results', $race) : route('admin.races.edit', $race) }}"
   class="d-block text-decoration-none rounded px-2 py-1 mb-1 text-white fw-bold text-truncate"
   style="background:{{ $race->gameColor() }};font-size:.62rem;letter-spacing:.03em;line-height:1.4;{{ $race->isPast() ? 'opacity:.6' : '' }}"
   title="{{ $race->title }} — {{ $race->scheduledAtUk()->format('H:i T') }}">
    {{ $race->scheduledAtUk()->format('H:i') }} {{ Str::limit($race->title, 16, '…') }}
</a>