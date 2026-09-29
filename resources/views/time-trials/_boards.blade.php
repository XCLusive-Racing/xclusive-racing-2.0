{{-- Platform boards (console / PC). Always separate leaderboards, never merged. --}}
@if(count($boards) > 1)
<nav class="xcl-tt__boards mb-4" aria-label="Platform">
    @foreach($boards as $key => $info)
    <a href="{{ $url($key) }}" class="xcl-tt__chip {{ $board === $key ? 'xcl-tt__chip--active' : '' }}">{{ $info['label'] }}</a>
    @endforeach
</nav>
@endif
