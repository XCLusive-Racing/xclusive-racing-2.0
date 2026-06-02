<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — XCLusive Racing</title>
    <link rel="icon" type="image/x-icon" href="/favicons/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-body" x-data="{
    sidebarOpen: false,
    sidebarCollapsed: false,
    sections: { site: true, events: true, config: true, ftp: true }
}">

{{-- Mobile overlay --}}
<div class="admin-sidebar-overlay" :class="{ open: sidebarOpen }" @click="sidebarOpen = false"></div>

{{-- Sidebar --}}
<aside class="admin-sidebar" :class="{ open: sidebarOpen, 'is-collapsed': sidebarCollapsed }">

    {{-- Collapse toggle --}}
    <div class="admin-sidebar-logo" :class="sidebarCollapsed ? 'justify-content-center' : 'justify-content-between'">
        <span x-show="!sidebarCollapsed" class="text-white fw-black text-uppercase" style="font-size:.7rem;letter-spacing:.08em">Admin Panel</span>
        <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="sidebar-collapse-btn d-none d-lg-flex">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      :d="sidebarCollapsed ? 'M9 18l6-6-6-6' : 'M15 18l-6-6 6-6'"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="py-1 flex-grow-1">

        {{-- Site --}}
        <div x-show="!sidebarCollapsed" class="admin-nav-section-header" @click="sections.site = !sections.site">
            <span>Site</span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="sections.site ? '' : 'transform:rotate(-90deg)'" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="sidebarCollapsed" class="admin-nav-section-divider"></div>

        <div x-show="sections.site || sidebarCollapsed">
            <a href="{{ route('home') }}" class="admin-nav-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1V10"/>
                </svg>
                <span x-show="!sidebarCollapsed">Homepage</span>
            </a>
            <a href="{{ route('race') }}" class="admin-nav-link" target="_blank">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                <span x-show="!sidebarCollapsed">View Events</span>
            </a>
        </div>

        @if(auth()->user()->canSeeUsers())
        {{-- Management — owner, admin, moderator --}}
        <div x-show="!sidebarCollapsed" class="admin-nav-section-header" @click="sections.config = !sections.config">
            <span>Management</span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="sections.config ? '' : 'transform:rotate(-90deg)'" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="sidebarCollapsed" class="admin-nav-section-divider"></div>

        <div x-show="sections.config || sidebarCollapsed">
            <a href="{{ route('admin.users.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                <span x-show="!sidebarCollapsed">Users</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->canManageEvents())
        {{-- Events — owner, admin, event_manager --}}
        <div x-show="!sidebarCollapsed" class="admin-nav-section-header" @click="sections.events = !sections.events">
            <span>Events</span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="sections.events ? '' : 'transform:rotate(-90deg)'" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="sidebarCollapsed" class="admin-nav-section-divider"></div>

        <div x-show="sections.events || sidebarCollapsed">
            <a href="{{ route('admin.races.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.index') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h10M3 18h6"/>
                </svg>
                <span x-show="!sidebarCollapsed">All Races</span>
            </a>
            <a href="{{ route('admin.calendar') }}"
               class="admin-nav-link {{ request()->routeIs('admin.calendar') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span x-show="!sidebarCollapsed">Calendar</span>
            </a>
            <a href="{{ route('admin.races.bulk-create') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.bulk-create') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span x-show="!sidebarCollapsed">Championship Scheduler</span>
            </a>
            <a href="{{ route('admin.races.create') }}"
               class="admin-nav-link {{ request()->routeIs('admin.races.create') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-show="!sidebarCollapsed">Create Single Race</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasAnyRole(['owner', 'admin']))
        {{-- Configuration — owner, admin only --}}
        <div x-show="!sidebarCollapsed" class="admin-nav-section-header" @click="sections.ftp = !sections.ftp">
            <span>Configuration</span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="sections.ftp ? '' : 'transform:rotate(-90deg)'" style="transition:transform .2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="sidebarCollapsed" class="admin-nav-section-divider"></div>

        <div x-show="sections.ftp || sidebarCollapsed">
            <a href="{{ route('admin.servers.index') }}"
               class="admin-nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <span x-show="!sidebarCollapsed">FTP Servers</span>
            </a>
        </div>
        @endif

    </nav>

    {{-- User / Logout --}}
    <div class="admin-sidebar-footer">
        <div x-show="!sidebarCollapsed" class="d-flex align-items-center gap-2 mb-2">
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
                <span x-show="!sidebarCollapsed">Logout</span>
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<div class="admin-main" :class="{ 'is-collapsed': sidebarCollapsed }">

    {{-- Header --}}
    <header class="admin-header">
        <div class="d-flex align-items-center gap-3">
            {{-- Mobile hamburger --}}
            <button class="btn p-1 d-lg-none border-0" @click="sidebarOpen = !sidebarOpen">
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

{{-- Toast container --}}
<div x-data="xcToasts()"
     @toast.window="add($event.detail)"
     class="position-fixed d-flex flex-column gap-2"
     style="top:1.5rem;right:1.5rem;z-index:9999;width:300px;pointer-events:none">
    <template x-for="t in toasts" :key="t.id">
        <div x-show="t.visible"
             x-transition:enter="xcl-toast-enter"
             x-transition:enter-start="xcl-toast-enter-start"
             x-transition:enter-end="xcl-toast-enter-end"
             x-transition:leave="xcl-toast-leave"
             x-transition:leave-start="xcl-toast-enter-end"
             x-transition:leave-end="xcl-toast-enter-start"
             class="d-flex align-items-start gap-2 px-3 py-3 rounded-3 shadow"
             style="pointer-events:auto"
             :style="`background:${t.color}`">
            <span x-text="t.message" class="text-white fw-bold flex-grow-1" style="font-size:.82rem;line-height:1.4"></span>
            <button @click="remove(t.id)"
                    class="btn-close btn-close-white flex-shrink-0 mt-1"
                    style="font-size:.6rem"></button>
        </div>
    </template>
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

<script>
function xcToasts() {
    return {
        toasts: [],
        add({ message, type = 'success' }) {
            const id = Date.now() + Math.random();
            const color = type === 'success' ? '#22c55e' : '#ef4444';
            this.toasts.push({ id, message, color, visible: true });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
        },
    };
}
</script>
</body>
</html>