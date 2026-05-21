<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <div class="mx-auto mb-3" style="width: 80px; height: 80px;">
                        <x-application-logo style="width: 80px; height: 80px;" />
                    </div>

                    <h4 class="mb-0">Login</h4>
                    <small class="text-muted">Masuk ke akun kamu</small>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-3">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            required
                            autofocus
                            autocomplete="username"
                        >

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                            autocomplete="current-password"
                        >

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="form-check-input"
                        >
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small">
                                Forgot your password?
                            </a>
                        @endif

                        <button type="submit" class="btn btn-primary px-4">
                            Log in
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
