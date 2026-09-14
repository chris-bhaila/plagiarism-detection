<x-guest-layout>
    <h1 class="text-[22px] font-semibold tracking-tight">Log in</h1>
    <p class="mt-1.5 text-[13.5px] text-slate-900">Enter your credentials to access your account.</p>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-7 grid gap-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center gap-2 text-[13px] text-slate-900 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember" class="w-[15px] h-[15px] rounded-sm accent-navy">
                Remember me
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[13px] font-medium text-navy hover:text-navy-light hover:underline">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full py-3 text-[14.5px]">
            Log in
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-7 text-center text-[13px] text-slate-900">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-navy hover:text-navy-light hover:underline">Register</a>
        </p>
    @endif
</x-guest-layout>
