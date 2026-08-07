{{-- Hoofdnavigatiebalk: vanilla JS hamburger toggle + hover dropdowns --}}
<nav class="navbar navbar-xcl navbar-expand-md fixed-top">
    <div class="navbar-xcl__topo" style="background-image:url('/topo.png');"></div>
    <div class="container-fluid px-5 align-content-center">

        {{-- Brand --}}
        <a class="navbar-brand d-none d-md-block" href="{{ url('/') }}">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="40">
        </a>

        {{-- Mobile: 3-column row — hamburger (left) | logo (center) | Sign In (right).
             Sign In lives here (not the dark top bar) only on mobile; desktop
             keeps its own Sign In further down in the collapse, untouched. --}}
        <div class="xcl-mobile-nav-row d-md-none">
            <button class="navbar-toggler border-0" type="button" aria-label="Toggle navigation">
                <svg width="24" height="24" fill="none" stroke="#7c3aed"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ url('/') }}" class="xcl-mobile-nav-row__logo">
                <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" class="xcl-mobile-nav-row__logo-img">
            </a>

            <div class="xcl-mobile-nav-row__right">
                @guest
                    <a href="{{ route('login') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white rounded-1 px-3"
                       style="background:rgba(124,58,237,.8);font-size:.7rem;padding-top:2px;padding-bottom:2px">
                        SIGN IN
                    </a>
                @endguest
            </div>
        </div>

        <div class="collapse navbar-collapse">

            {{-- Mobile: Profile + Admin at top of hamburger menu --}}
            @auth
            <div class="d-flex d-md-none align-items-center gap-2 py-2 mb-1 border-bottom border-secondary-subtle">
                <a href="{{ route('profile') }}"
                   class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                    <x-rank-avatar :user="auth()->user()" :size="28" :badge="false" />
                    PROFILE
                </a>
                @php $unreadMobile = auth()->user()->totalUnreadCount(); @endphp
                <a href="{{ route('messages.index') }}"
                   class="position-relative d-flex align-items-center text-xcl-purple ms-2"
                   style="font-size:1.1rem;line-height:1" title="Inbox">
                    <i class="fa-regular fa-envelope"></i>
                    @if($unreadMobile > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-white fw-bold"
                              style="background:#dc2626;font-size:.55rem;min-width:15px;padding:2px 3px;transform:translate(-40%,-30%) !important">
                            {{ $unreadMobile > 99 ? '99+' : $unreadMobile }}
                        </span>
                    @endif
                </a>
                @if(auth()->user()->canAccessAdminPanel())
                <a href="{{ route(auth()->user()->adminLandingRoute()) }}"
                   class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple ms-auto">
                    ADMIN
                </a>
                @endif
            </div>
            @endauth

            {{-- Nav links --}}
            <ul class="navbar-nav mx-auto gap-md-4">

                <li class="nav-item d-md-none">
                    <a class="nav-link" href="{{ url('/') }}">HOME</a>
                </li>

                {{-- ABOUT --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link" href="{{ url('/#about') }}">ABOUT</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle ABOUT submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li class="d-md-none"><a class="xcl-dropdown-item" href="{{ url('/#about') }}">OVERVIEW</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ url('/#partners') }}">PARTNERS</a></li>
                    </ul>
                </li>

                {{-- NEWS --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link xcl-nav-news" href="{{ route('news.index') }}">NEWS</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle NEWS submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li class="d-md-none"><a class="xcl-dropdown-item" href="{{ route('news.index') }}">OVERVIEW</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('live') }}">BROADCASTS</a></li>
                    </ul>
                </li>

                {{-- TEAM --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link" href="{{ route('team') }}">TEAM</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle TEAM submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li class="d-md-none"><a class="xcl-dropdown-item" href="{{ route('team') }}">OVERVIEW</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.pro.index') }}">PROFESSIONAL</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.esports.index') }}">ESPORTS</a></li>
                        <li><a class="xcl-dropdown-item" href="#">STAFF</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('coaching.index') }}">COACHING</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('team.join') }}">JOIN THE TEAM</a></li>
                    </ul>
                </li>

                {{-- XCL EVENTS --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link" href="{{ route('events.index') }}">EVENTS</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle EVENTS submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li class="d-md-none"><a class="xcl-dropdown-item" href="{{ route('events.index') }}">OVERVIEW</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ url('/events?type=time-trial') }}">TIME TRIALS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('championships.index') }}">CHAMPIONSHIPS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('calendar') }}">CALENDAR</a></li>
                    </ul>
                </li>

                {{-- RACING --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link" href="#">RACING</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle RACING submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li><a class="xcl-dropdown-item" href="{{ route('drivers.index') }}">LEADERBOARD</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('results.index') }}">RESULTS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('bop.index') }}">BOPs</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('reports.index') }}">REPORTS</a></li>
                    </ul>
                </li>

                {{-- SHOP --}}
                <li class="nav-item position-relative" data-dropdown>
                    <div class="xcl-nav-item-row">
                        <a class="nav-link" href="#">SHOP</a>
                        <button type="button" class="xcl-nav-chevron d-md-none" data-mobile-dropdown-toggle
                                aria-expanded="false" aria-label="Toggle SHOP submenu">
                            <svg class="xcl-nav-chevron__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li><a class="xcl-dropdown-item" href="https://raven.gg/stores/xclusive-esports/" target="_blank">MERCHANDISE</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('coaching.index') }}">COACHING</a></li>
                        <li><a class="xcl-dropdown-item" href="#">SETUPS</a></li>
                    </ul>
                </li>

            </ul>

            {{-- Right: Discord + auth (desktop only) --}}
            <div class="d-none d-md-flex align-items-center gap-3">

                <a href="https://paypal.me/xcl789" target="_blank" rel="noopener"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex align-items-center gap-2 bg-xcl-purple">
                    <i class="fa-brands fa-paypal"></i>
                    SUPPORT
                </a>

                @auth
                    @if(auth()->user()->canAccessAdminPanel())
                        <a href="{{ route(auth()->user()->adminLandingRoute()) }}"
                           class="btn btn-sm fw-bold text-uppercase text-white d-none d-md-inline-flex bg-xcl-purple">
                            ADMIN
                        </a>
                    @endif

                    @php $unread = auth()->user()->totalUnreadCount(); @endphp
                    <a href="{{ route('messages.index') }}"
                       class="position-relative d-flex align-items-center text-xcl-purple"
                       style="font-size:1.15rem;line-height:1"
                       title="Inbox">
                        <i class="fa-regular fa-envelope"></i>
                        @if($unread > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-white fw-bold"
                                  style="background:#dc2626;font-size:.6rem;min-width:16px;padding:2px 4px;transform:translate(-40%,-30%) !important">
                                {{ $unread > 99 ? '99+' : $unread }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('profile') }}"
                       class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <x-rank-avatar :user="auth()->user()" :size="32" :badge="false" />
                        PROFILE
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple rounded-1 px-4">
                        SIGN IN
                    </a>

                    <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                         alt="XCLusive" width="32" height="32"
                         style="object-fit:contain;">
                @endauth

            </div>
        </div>
    </div>
</nav>
