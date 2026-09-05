<footer style="background:#0d0d14;border-top:1px solid rgba(255,255,255,.06)">

    <style>
        .xcl-footer__grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 2.5rem;
        }
        .xcl-footer__brand { grid-column: span 4; }
        .xcl-footer__links {
            grid-column: span 8;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        .xcl-footer__platform-badges { display: flex; flex-wrap: wrap; gap: .5rem; }
        .xcl-footer__platform-badge {
            display: inline-flex;
            align-items: center;
            padding: .3rem .75rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.15);
            color: #9ca3af;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .xcl-footer__social { display: flex; gap: .75rem; margin-top: 1rem; }
        .xcl-footer__social-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: .5rem;
            background: rgba(255,255,255,.06);
            color: #9ca3af;
            text-decoration: none;
            transition: all .2s;
        }
        .xcl-footer__cta {
            display: flex;
            gap: 2.5rem;
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 2rem 0;
            margin-top: 1rem;
        }
        .xcl-footer__cta > div { flex: 1 1 0; }
        .xcl-footer__cta-label {
            color: #9ca3af;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-bottom: .4rem;
        }
        .xcl-footer__discord-btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1.5px solid #5865F2;
            background: transparent;
            color: #5865F2;
            padding: .5rem 1rem;
            border-radius: .5rem;
            font-weight: 800;
            text-transform: uppercase;
            font-size: .78rem;
            text-decoration: none;
            transition: all .2s;
        }
        .xcl-footer__discord-btn:hover { background: #5865F2; color: #fff; }

        @media (max-width: 991.98px) {
            .xcl-footer__grid { grid-template-columns: 1fr; }
            .xcl-footer__brand { grid-column: span 1; }
            .xcl-footer__links { grid-column: span 1; grid-template-columns: repeat(2, 1fr); }
            .xcl-footer__cta { flex-direction: column; gap: 1.75rem; }
        }

        @media (max-width: 575.98px) {
            .xcl-footer__brand { text-align: center; }
            .xcl-footer__brand .xcl-footer__platform-badges,
            .xcl-footer__brand .xcl-footer__social { justify-content: center; }
            .footer-link { display: flex; align-items: center; min-height: 44px; }
        }
    </style>

    <div class="container-xl px-4 py-5">
        <div class="xcl-footer__grid">

            {{-- Brand --}}
            <div class="xcl-footer__brand">
                <img src="/logo.png" alt="XCLusive" height="48" class="mb-4">
                <p style="color:#6b7280;font-size:.9rem;line-height:1.7;max-width:280px">
                    Dominating sim racing from console to PC.<br>
                    Join the pride.
                </p>

                <div class="xcl-footer__platform-badges">
                    <span class="xcl-footer__platform-badge">ACC</span>
                    <span class="xcl-footer__platform-badge">iRacing</span>
                    <span class="xcl-footer__platform-badge">Le Mans Ultimate</span>
                </div>

                <div class="xcl-footer__social">
                    <a href="{{ config('xcl.discord_url') }}" target="_blank"
                       class="xcl-footer__social-icon"
                       onmouseover="this.style.background='#7c3aed';this.style.color='white'"
                       onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='#9ca3af'"
                       title="Discord">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/xclusive_esport/" target="_blank"
                       class="xcl-footer__social-icon"
                       onmouseover="this.style.background='#db2777';this.style.color='white'"
                       onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='#9ca3af'"
                       title="Instagram">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@XCL_TV" target="_blank"
                       class="xcl-footer__social-icon"
                       onmouseover="this.style.background='#ef4444';this.style.color='white'"
                       onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='#9ca3af'"
                       title="YouTube">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    {{-- TODO: no TikTok profile URL configured anywhere in the codebase yet --}}
                    <a href="#"
                       class="xcl-footer__social-icon"
                       onmouseover="this.style.background='#7c3aed';this.style.color='white'"
                       onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='#9ca3af'"
                       title="TikTok">
                        <i class="fa-brands fa-tiktok" style="font-size:16px"></i>
                    </a>
                    {{-- TODO: no X (Twitter) profile URL configured anywhere in the codebase yet --}}
                    <a href="#"
                       class="xcl-footer__social-icon"
                       onmouseover="this.style.background='#db2777';this.style.color='white'"
                       onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='#9ca3af'"
                       title="X">
                        <i class="fa-brands fa-x-twitter" style="font-size:16px"></i>
                    </a>
                </div>
            </div>

            {{-- Link columns --}}
            <div class="xcl-footer__links">

                <div>
                    <div class="fw-black text-white text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.1em">Navigate</div>
                    <div class="d-flex flex-column gap-2">
                        {{-- TODO: no "about" route or homepage section exists --}}
                        <a href="#" class="footer-link">About Us</a>
                        <a href="{{ route('team') }}" class="footer-link">Teams</a>
                        <a href="{{ route('events.index') }}" class="footer-link">Events</a>
                        <a href="{{ route('calendar') }}" class="footer-link">Calendar</a>
                        <a href="{{ route('home') }}#partners" class="footer-link">Partners</a>
                        {{-- TODO: no "contact" route exists --}}
                        <a href="#" class="footer-link">Contact</a>
                    </div>
                </div>

                <div>
                    <div class="fw-black text-white text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.1em">Racing</div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('championships.index') }}" class="footer-link">Championships</a>
                        {{-- TODO: no standalone standings route exists --}}
                        <a href="#" class="footer-link">Standings</a>
                        <a href="{{ route('results.index') }}" class="footer-link">Results</a>
                        {{-- TODO: no public XCL Rating / licences info route exists --}}
                        <a href="#" class="footer-link">XCL Rating and Licences</a>
                        {{-- TODO: no rules and regulations route exists --}}
                        <a href="#" class="footer-link">Rules and Regulations</a>
                    </div>
                </div>

                <div>
                    <div class="fw-black text-white text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.1em">Community</div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('news.index') }}" class="footer-link">News</a>
                        <a href="{{ route('coaching.index') }}" class="footer-link">Coaching</a>
                        <a href="{{ route('team.join') }}" class="footer-link">Join The Team</a>
                        <a href="https://raven.gg/stores/xclusive-esports/" target="_blank" class="footer-link">Merchandise</a>
                        <a href="{{ config('xcl.discord_url') }}" target="_blank" class="footer-link">Discord</a>
                    </div>
                </div>

                <div>
                    <div class="fw-black text-white text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.1em">Account</div>
                    <div class="d-flex flex-column gap-2">
                        @auth
                            <a href="{{ route('profile') }}" class="footer-link">My Profile</a>
                            {{-- TODO: race history/upcoming events live on the profile page but have no dedicated route or section anchor --}}
                            <a href="#" class="footer-link">My Races</a>
                            <a href="{{ route('racing-teams.index') }}" class="footer-link">My Team</a>
                            {{-- TODO: no dashboard route exists --}}
                            <a href="#" class="footer-link">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="footer-link">Log In</a>
                            <a href="{{ route('register') }}" class="footer-link">Register</a>
                            <a href="{{ route('team.join') }}" class="footer-link">Join The Team</a>
                        @endauth
                    </div>
                </div>

            </div>

        </div>

        {{-- CTA strip --}}
        <div class="xcl-footer__cta">
            <div>
                <div class="xcl-footer__cta-label">Merchandise</div>
                <p style="color:#6b7280;font-size:.85rem;margin-bottom:1rem">Represent the pride. Wear the purple.</p>
                <a href="https://raven.gg/stores/xclusive-esports/" target="_blank"
                   class="btn btn-sm fw-black text-uppercase text-white px-3 py-2"
                   style="background:linear-gradient(135deg,#7c3aed,#db2777);font-size:.78rem">
                    SHOP NOW →
                </a>
            </div>
            <div>
                <div class="xcl-footer__cta-label">Community</div>
                <p style="color:#6b7280;font-size:.85rem;margin-bottom:1rem">11,000+ racers. One Discord.</p>
                <a href="{{ config('xcl.discord_url') }}" target="_blank" class="xcl-footer__discord-btn">
                    JOIN DISCORD
                </a>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div style="border-top:1px solid rgba(255,255,255,.06);padding:1.25rem 1.5rem">
        <div class="container-xl d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span style="color:#4b5563;font-size:.78rem">
                &copy; {{ date('Y') }} XCLusive Gaming Events. All rights reserved.
                &nbsp;&middot;&nbsp;
                <a href="{{ route('privacy') }}" class="text-decoration-none" style="color:#6b7280">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                {{-- TODO: no terms of service route or view exists --}}
                <a href="#" class="text-decoration-none" style="color:#6b7280">Terms of Service</a>
            </span>
            <span class="fw-black text-uppercase fst-italic" style="font-size:.72rem;color:#4b5563;letter-spacing:.06em">
                THE LION IS BORN TO DOMINATE
            </span>
        </div>
    </div>

</footer>
