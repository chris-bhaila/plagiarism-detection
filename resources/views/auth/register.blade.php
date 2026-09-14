<x-guest-layout>
    <h1 class="text-[22px] font-semibold tracking-tight">Create an account</h1>
    <p class="mt-1.5 text-[13.5px] text-slate-900">Register as a student to start submitting assignments.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-7 grid gap-5">
        @csrf

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="faculty" value="Faculty" />
                <select id="faculty" name="faculty" required
                    class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                    <option value="" disabled @selected(old('faculty') === null)>Select faculty</option>
                    @foreach (\App\Models\User::FACULTIES as $faculty)
                        <option value="{{ $faculty }}" @selected(old('faculty') === $faculty)>{{ $faculty }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('faculty')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="semester" value="Semester" />
                <select id="semester" name="semester" required
                    class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                    <option value="" disabled @selected(old('semester') === null)>Select semester</option>
                    @for ($s = 1; $s <= 8; $s++)
                        <option value="{{ $s }}" @selected((int) old('semester') === $s)>Semester {{ $s }}</option>
                    @endfor
                </select>
                <x-input-error :messages="$errors->get('semester')" class="mt-1.5" />
            </div>
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full py-3 text-[14.5px]">
            Create account
        </x-primary-button>
    </form>

    <p class="mt-7 text-center text-[13px] text-slate-900">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-navy hover:text-navy-light hover:underline">Log in</a>
    </p>
</x-guest-layout>
