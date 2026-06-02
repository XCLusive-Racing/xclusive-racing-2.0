@extends('layouts.app')

@section('title', 'Sign In - XCLusive Racing')

@section('content')
<div class="xcl-auth-page py-5">
    <div class="xcl-auth-page__topo" style="background-image:url('/topo.png');"></div>
    <div class="xcl-auth-card">

        <div class="text-center mb-4">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive Racing" height="40" class="mb-3">
            <h1 class="fs-3 fw-black text-uppercase fst-italic text-white mb-1">Sign In</h1>
            <p class="text-white-50 small mb-0">Welcome back to XCLusive Racing</p>
        </div>

        @if ($errors->any())
        <div class="alert border-0 rounded-3 mb-4 py-2 px-3" style="background:rgba(239,68,68,.15); border-left:3px solid #ef4444 !important; border-left-width:3px !important;">
            @foreach ($errors->all() as $error)
                <div class="small text-danger">{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       placeholder="your@email.com"
                       class="form-control xcl-auth-input"
                       autocomplete="off">
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Password</label>
                <input type="password" name="password" required
                       placeholder="••••••••"
                       class="form-control xcl-auth-input"
                       autocomplete="new-password">
            </div>

            <button type="submit" class="btn w-100 fw-black text-uppercase text-white py-3 mb-4 bg-gradient-xcl border-0"
                    style="letter-spacing:.06em; border-radius:10px;">
                Sign In
            </button>

            <p class="text-center mb-0" style="color:rgba(255,255,255,.4); font-size:.85rem;">
                No account yet?
                <a href="{{ route('register') }}" class="fw-bold text-xcl-purple text-decoration-none">Sign up</a>
            </p>
        </form>

    </div>
</div>
@endsection