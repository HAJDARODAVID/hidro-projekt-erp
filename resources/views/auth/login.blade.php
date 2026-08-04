@extends('layouts.app')

@section('content')
<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="card login-card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <img src="{{ asset('images/hidroprojekt_logo.png') }}" alt="Hidro-projekt" class="login-logo mb-4">
                            <h1 class="h4 fw-bold mb-1">{{ __('Welcome back') }}</h1>
                            <p class="text-muted small mb-0">{{ __('Sign in to continue to your account') }}</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold">{{ __('Email Address') }}</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
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
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                    <input id="password" type="password" class="form-control border-start-0 border-end-0 ps-0 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                                    <span class="input-group-text bg-light border-start-0" role="button" id="togglePassword">
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
        min-height: 100vh;
        display: flex;
        justify-content: center;
        padding-top: 5vh;
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
</script>
@endsection
