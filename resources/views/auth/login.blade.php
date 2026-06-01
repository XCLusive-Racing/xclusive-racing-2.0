@extends('layouts.app')

@section('title', 'Login - XCLusive Racing')

@section('content')
<div class="xcl-page d-flex align-items-center justify-content-center bg-light py-5">
    <div class="bg-white rounded-3 shadow p-4 p-md-5 w-100" style="max-width:420px">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-0">SIGN IN</h2>
            <a href="{{ url('/') }}" class="text-secondary text-decoration-none fs-5">&times;</a>
        </div>

        @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div class="small">{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase text-dark">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       placeholder="your@email.com" class="form-control border-secondary"
                       autocomplete="off">
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase text-dark">Password</label>
                <input type="password" name="password" required
                       placeholder="••••••••" class="form-control border-secondary"
                       autocomplete="new-password">
            </div>

            <button type="submit" class="btn w-100 fw-bold text-uppercase text-white py-2 mb-3 bg-xcl-purple">
                SIGN IN
            </button>

            <p class="text-center text-secondary small mb-0">
                No account yet?
                <a href="{{ route('register') }}" class="fw-bold text-xcl-purple text-decoration-none">Sign up</a>
            </p>
        </form>
    </div>
</div>
@endsection