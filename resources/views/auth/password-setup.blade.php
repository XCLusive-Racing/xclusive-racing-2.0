@extends('layouts.app')

@section('title', 'Set Your Password - XCLusive Racing')

@section('content')
<div class="xcl-page d-flex align-items-center justify-content-center bg-light py-5">
    <div class="bg-white rounded-3 shadow p-4 p-md-5 w-100" style="max-width:420px">
        <div class="mb-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-1">Set your password</h2>
            <p class="text-secondary small mb-0">Welcome, {{ auth()->user()->name }}. Choose a password to activate your account.</p>
        </div>

        @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div class="small">{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.setup.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase text-dark">New password</label>
                <input type="password" name="password" required
                       placeholder="••••••••" class="form-control border-secondary"
                       minlength="8">
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase text-dark">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       placeholder="••••••••" class="form-control border-secondary"
                       minlength="8">
            </div>

            <button type="submit" class="btn w-100 fw-bold text-uppercase text-white py-2"
                    style="background:#7c3aed;">
                Activate account
            </button>
        </form>
    </div>
</div>
@endsection