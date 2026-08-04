@extends('layouts.app-login')

@section('content')
@php
    $themeCookie = request()->cookie('theme');
    $loginDark = $themeCookie !== null ? $themeCookie === 'dark' : config('theme.login_dark');
@endphp
<div class="login-page @if($loginDark) login-page-dark @endif" id="loginPage">
    <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="{{ __('Toggle theme') }}">
        <i class="bi bi-moon-stars-fill" id="themeToggleIconDark"></i>
        <i class="bi bi-sun-fill" id="themeToggleIconLight"></i>
    </button>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="card login-card border-0 shadow-lg">
                    <div class="card-body px-md-5 py-4">
                        <div class="text-center mb-4">
                            <span class="login-logo-badge d-inline-block">
                                <img src="{{ asset('images/hidroprojekt_logo_highres.png') }}" alt="Hidro-projekt" class="login-logo">
                            </span>
                            <hr>
                            {{-- <h1 class="h4 fw-bold mb-1">{{ __('Welcome back') }}</h1>
                            <p class="text-muted small mb-0">{{ __('Sign in to continue to your account') }}</p> --}}
                        </div>

                        <form method="POST" action="{{ route('login') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold">{{ __('Email Address') }}</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                    <input id="email" type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@example.com">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label small fw-semibold">{{ __('Password') }}</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                    <input id="password" type="password" class="form-control border-start-0 border-end-0 ps-0 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                                    <span class="input-group-text border-start-0" role="button" id="togglePassword">
                                        <i class="bi bi-eye text-muted" id="togglePasswordIcon"></i>
                                    </span>

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label small" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                                {{ __('Login') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    nav.navbar {
        display: none;
    }

    .login-page {
        position: relative;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        padding-top: 4vh;
        background-color: #eef1f5;
    }

    .theme-toggle-btn {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dee2e6;
        background-color: #ffffff;
        color: #495057;
        cursor: pointer;
        font-size: 1.1rem;
        z-index: 10;
    }

    .theme-toggle-btn #themeToggleIconLight {
        display: none;
    }

    .login-page-dark .theme-toggle-btn {
        background-color: #1a1f2b;
        border-color: #323a4a;
        color: #e2e5eb;
    }

    .login-page-dark .theme-toggle-btn #themeToggleIconDark {
        display: none;
    }

    .login-page-dark .theme-toggle-btn #themeToggleIconLight {
        display: inline-block;
    }

    .login-card,
    .login-card .form-control,
    .login-card .input-group-text,
    .login-card .btn-primary {
        border-radius: 0;
    }

    .login-logo {
        max-width: 220px;
        width: 100%;
        height: auto;
        display: block;
    }

    .login-page-dark .login-logo-badge {
        /* padding: 0.75rem 1.25rem; */
    }

    .login-card .input-group-text {
        border-color: #dee2e6;
    }

    .login-card .form-control {
        border-color: #dee2e6;
    }

    .login-card .form-control:focus {
        box-shadow: none;
        border-color: #86b7fe;
    }

    .login-card .input-group:focus-within .input-group-text,
    .login-card .input-group:focus-within .form-control {
        border-color: #86b7fe;
    }

    .login-page-dark {
        background-color: #12161f;
    }

    .login-page-dark .login-card {
        background-color: #1a1f2b;
        color: #e2e5eb;
    }

    .login-page-dark .login-card .text-muted {
        color: #8891a3 !important;
    }

    .login-page-dark .login-card .form-label {
        color: #c3c8d3;
    }

    .login-page-dark .login-card .input-group-text {
        background-color: #232937;
        border-color: #323a4a;
        color: #8891a3;
    }

    .login-page-dark .login-card .form-control {
        background-color: #232937;
        border-color: #323a4a;
        color: #e2e5eb;
    }

    .login-page-dark .login-card .form-control::placeholder {
        color: #5c6577;
    }

    .login-page-dark .login-card .form-control:focus {
        background-color: #232937;
        color: #e2e5eb;
        box-shadow: none;
        border-color: #4d7fff;
    }

    .login-page-dark .login-card .input-group:focus-within .input-group-text,
    .login-page-dark .login-card .input-group:focus-within .form-control {
        border-color: #4d7fff;
    }

    .login-page-dark .login-card .form-check-label {
        color: #c3c8d3;
    }

    .login-page-dark .login-card .form-check-input {
        background-color: #232937;
        border-color: #323a4a;
    }

    .login-page-dark .login-card .form-check-input:checked {
        background-color: #4d7fff;
        border-color: #4d7fff;
    }

    .login-page-dark .login-card .btn-primary {
        background-color: #4d7fff;
        border-color: #4d7fff;
    }

    .login-page-dark .login-card .btn-primary:hover,
    .login-page-dark .login-card .btn-primary:active {
        background-color: #3a68e0;
        border-color: #3a68e0;
    }
</style>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const password = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        const isPassword = password.type === 'password';

        password.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    });

    document.getElementById('themeToggle').addEventListener('click', function () {
        const page = document.getElementById('loginPage');
        const isDark = page.classList.toggle('login-page-dark');
        const oneYear = 60 * 60 * 24 * 365;

        document.cookie = 'theme=' + (isDark ? 'dark' : 'light') + '; path=/; max-age=' + oneYear + '; SameSite=Lax';
    });
</script>
@endsection
