@extends('layouts.app')

@section('no-sidebar', true)
@section('title', 'Forgot Password - XCLusive Racing')

@section('content')
<div class="xcl-auth-page py-5">
    <div class="xcl-auth-page__topo" style="background-image:url('/topo.png');"></div>
    <div class="xcl-auth-card">

        <div class="text-center mb-4">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive Racing" height="40" class="mb-3">
            <h1 class="fs-3 fw-black text-uppercase fst-italic text-white mb-1">Forgot Password</h1>
            <p class="text-white-50 small mb-0">Password reset by email isn't live yet</p>
        </div>

        <div class="alert border-0 rounded-3 mb-4 py-3 px-3 text-center" style="background:rgba(124,58,237,.15); border-left:3px solid #7c3aed !important; border-left-width:3px !important;">
            <div class="small text-white-50">
                We're still setting this up. Reach out to an admin on Discord in the meantime and they'll reset it for you.
            </div>
        </div>

        <p class="text-center mb-0" style="color:rgba(255,255,255,.4); font-size:.85rem;">
            <a href="{{ route('login') }}" class="fw-bold text-xcl-purple text-decoration-none">Back to sign in</a>
        </p>

    </div>
</div>
@endsection
