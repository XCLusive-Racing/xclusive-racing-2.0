<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('xcl.name') }}</title>
    <link rel="icon" type="image/x-icon" href="/favicons/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-body">

{{-- Mobile overlay --}}
<div class="admin-sidebar-overlay"></div>

{{-- Sidebar --}}
<aside class="admin-sidebar">

    {{-- Collapse toggle --}}
    <div class="admin-sidebar-logo">
        <span class="collapse-hide text-white fw-black text-uppercase" style="font-size:.7rem;letter-spacing:.08em">Admin Panel</span>
        <button data-sidebar-collapse class="sidebar-collapse-btn d-none d-lg-flex">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path data-collapse-icon stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="py-1 flex-grow-1">

        {{-- Site --}}
        <div class="admin-nav-section-header" data-section="site">
            <span>Site</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="site">
            <a href="{{ route('home') }}" class="admin-nav-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1V10"/>
                </svg>
                <span>Homepage</span>
            </a>
        </div>

        {{-- Management --}}
        <div class="admin-nav-section-header" data-section="events">
            <span>Management</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="events">
            @if(auth()->user()->canManageEvents())
            <a href="{{ route('admin.championships.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.championships.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <span>Championships</span>
            </a>
            <a href="{{ route('admin.races.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.index') || request()->routeIs('admin.races.show') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h10M3 18h6"/>
                </svg>
                <span>All Races</span>
            </a>
            <a href="{{ route('admin.calendar') }}"
               class="admin-nav-link {{ request()->routeIs('admin.calendar') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Calendar</span>
            </a>
            @endif
            @if(auth()->user()->hasAnyRole(['owner', 'admin']))
            <a href="{{ route('admin.media.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
                <span>Media Library</span>
            </a>
            @endif
            @if(auth()->user()->canSeeUsers())
            <a href="{{ route('admin.users.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                <span>Users</span>
            </a>
            @endif
        </div>

        @if(auth()->user()->canManageEvents())
        {{-- Events --}}
        <div class="admin-nav-section-header" data-section="config">
            <span>Events</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="config">
            <a href="{{ route('admin.races.create') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.create') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Create Race</span>
            </a>
            <a href="{{ route('admin.races.custom-create') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.custom-create') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Custom Race</span>
            </a>
            <a href="{{ route('admin.team-events.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.team-events.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
                <span>+ Team Event</span>
            </a>
        </div>
        @endif

        {{-- Racing --}}
        <div class="admin-nav-section-header" data-section="racing">
            <span>Racing</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="racing">
            @if(auth()->user()->canManageEvents())
            <a href="{{ route('admin.bops.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.bops.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                <span>BOPs</span>
            </a>
            <a href="{{ route('admin.reports.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Reports</span>
            </a>
            @endif
        </div>

        @if(auth()->user()->canBroadcast())
        {{-- Content --}}
        <div class="admin-nav-section-header" data-section="content">
            <span>Content</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="content">
            <a href="{{ route('admin.news.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.news.index') || request()->routeIs('admin.news.edit') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <span>All Articles</span>
            </a>
            <a href="{{ route('admin.news.create') }}"
               class="admin-nav-link {{ request()->routeIs('admin.news.create') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Create Article</span>
            </a>
            <a href="{{ route('admin.news.tags.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.news.tags.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span>Tags</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasAnyRole(['owner', 'admin']))
        {{-- Configuration --}}
        <div class="admin-nav-section-header" data-section="ftp">
            <span>Configuration</span>
            <svg data-section-arrow width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="admin-nav-section-divider" style="display:none"></div>

        <div data-section-content="ftp">
            <a href="{{ route('admin.servers.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <span>FTP Servers</span>
            </a>
            @if(auth()->user()->isOwner())
            <a href="{{ route('admin.event-formats.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.event-formats.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Event Formats</span>
            </a>
            <a href="{{ route('admin.rating-config.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.rating-config.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>Rating Config</span>
            </a>
            @endif
        </div>
        @endif

    </nav>

    {{-- User / Logout --}}
    <div class="admin-sidebar-footer">
        <div class="collapse-hide d-flex align-items-center gap-2 mb-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                 style="width:30px;height:30px;font-size:.75rem;background:linear-gradient(135deg,#7c3aed,#db2777)">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div style="min-width:0">
                <div class="text-white fw-bold text-truncate" style="font-size:.75rem">{{ auth()->user()->name }}</div>
                <div style="font-size:.6rem;color:#6b7280;text-transform:uppercase;font-weight:700">{{ auth()->user()->roles->pluck('name')->join(', ') }}</div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-100 btn btn-sm fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2"
                    style="background:rgba(255,255,255,.06);color:#6b7280;border:1px solid rgba(255,255,255,.08);font-size:.68rem">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="collapse-hide">Logout</span>
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<div class="admin-main">

    {{-- Header --}}
    <header class="admin-header">
        <div class="d-flex align-items-center gap-3">
            {{-- Mobile hamburger --}}
            <button class="btn p-1 d-lg-none border-0" data-sidebar-toggle>
                <svg width="22" height="22" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Breadcrumb --}}
            <div class="admin-breadcrumb">
                <a href="{{ route('admin.races.index') }}">Admin</a>
                <span class="sep">/</span>
                <span class="current">@yield('page-title', 'Dashboard')</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            @yield('page-actions')
        </div>
    </header>

    {{-- Page content --}}
    <main class="admin-content">
        @yield('content')
    </main>
</div>

{{-- Flash → toast bridge --}}
@if(session('success') || session('error') || $errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(session('success')), type: 'success' } }));
        @endif
        @if(session('error'))
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(session('error')), type: 'error' } }));
        @endif
        @if($errors->any())
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js($errors->first()), type: 'error' } }));
        @endif
    });
</script>
@endif

@stack('scripts')

</body>
</html>
