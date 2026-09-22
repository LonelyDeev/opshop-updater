<x-layouts.guest title="تعیین گذرواژه جدید">
    <div class="card animate-slide-up bg-white/95 p-6 backdrop-blur dark:bg-zinc-900/95 sm:p-8">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <x-field label="ایمیل" for="email" required>
                <x-input id="email" type="email" name="email" :value="old('email', $email ?? '')" icon="mail" placeholder="you@example.com" required autofocus
                         :class="$errors->has('email') ? 'input-error' : ''" />
            </x-field>

            <x-field label="گذرواژه جدید" for="password" required>
                <x-input id="password" type="password" name="password" icon="lock" placeholder="••••••••" required autocomplete="new-password"
                         :class="$errors->has('password') ? 'input-error' : ''" />
            </x-field>

            <x-field label="تکرار گذرواژه جدید" for="password-confirm" required>
                <x-input id="password-confirm" type="password" name="password_confirmation" icon="lock" placeholder="••••••••" required autocomplete="new-password" />
            </x-field>

            @if($errors->any())
                <div class="flex items-center gap-2.5 rounded-xl bg-rose-50 px-3.5 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20" role="alert">
                    <x-icon name="alert-circle" class="size-4.5 shrink-0" />
                    {{ $errors->first() }}
                </div>
            @endif

            <x-btn type="submit" class="w-full" size="lg" icon="check">
                تغییر گذرواژه
            </x-btn>
        </form>

        <p class="mt-5 text-center text-sm">
            <a href="{{ route('login') }}" class="link">بازگشت به ورود</a>
        </p>
    </div>
</x-layouts.guest>
