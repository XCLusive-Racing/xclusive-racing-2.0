<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XCLusive Racing - The Lion is Born to Dominate')</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>

{{-- Navbar --}}
<nav class="navbar navbar-xcl navbar-expand-md fixed-top" x-data="{ open: false }">
    <div class="container-xl px-4">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="/logo.png" alt="XCLusive" height="40">
        </a>

        <button class="navbar-toggler border-0" @click="open = !open">
            <svg width="24" height="24" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="collapse navbar-collapse" :class="{ 'show': open }">
            <ul class="navbar-nav mx-auto gap-md-3">
                <li class="nav-item"><a class="nav-link" href="{{ url('/#about') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/#teams') }}">Teams</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/race') }}">Race</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/#partners') }}">Partners</a></li>
                <li class="nav-item"><a class="nav-link" href="https://raven.gg/stores/xclusive-esports/" target="_blank">Merchandise</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex"
                   style="background:#7c3aed;">JOIN DISCORD</a>

                @auth
                    <a href="{{ route('profile') }}" class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        PROFILE
                    </a>
                @else
                    <a href="{{ route('login') }}" class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        PROFILE
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- Page content --}}
@yield('content')

{{-- Footer --}}
<footer class="footer-xcl py-5 px-4">
    <div class="container-xl">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <img src="/logo.png" alt="XCLusive" height="60" class="mb-3">
                <p class="text-secondary">Dominating sim racing from console to PC. Join the pride.</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-white fw-black mb-3" style="text-transform:uppercase">QUICK LINKS</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ url('/#about') }}">About Us</a>
                    <a href="{{ url('/#teams') }}">Teams</a>
                    <a href="{{ url('/race') }}">Race</a>
                </div>
            </div>
            <div class="col-md-4">
                <h6 class="text-white fw-black mb-3" style="text-transform:uppercase">CONNECT</h6>
                <div class="d-flex gap-3">
                    <a href="https://discord.gg/AHNTFY9Uuu" target="_blank">Discord</a>
                    <a href="https://www.instagram.com/xclusive_esport/" target="_blank">Instagram</a>
                    <a href="https://www.youtube.com/@XCL_TV" target="_blank">YouTube</a>
                </div>
            </div>
        </div>
        <div class="border-top border-secondary pt-4 text-center text-secondary small">
            &copy; {{ date('Y') }} XCLusive Gaming Events. All rights reserved. The lion is born to dominate.
        </div>
    </div>
</footer>

</body>
</html>