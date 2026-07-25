<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <p class="login-box-msg">Sign in to start your session</p>
        <div class="input-group mb-3">
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="input-group mb-3">
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row">
            <div class="col-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">Remember Me</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-primary w-100">Log In</button>
            </div>
        </div>
        <p class="mt-3 mb-1"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
        <p class="mb-0"><a href="{{ route('register') }}" class="text-center">Register a new membership</a></p>
        @if (session('status'))
            <div class="alert alert-success mt-3 mb-0">{{ session('status') }}</div>
        @endif
    </form>
</x-guest-layout>
