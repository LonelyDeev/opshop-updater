<x-layouts.guest title="تأیید گذرواژه">
    <div class="card animate-slide-up bg-white/95 p-6 backdrop-blur dark:bg-zinc-900/95 sm:p-8">
        <p class="mb-6 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
            برای ادامه، لطفاً گذرواژه خود را تأیید کنید.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <x-field label="گذرواژه" for="password" required>
                <x-input id="password" type="password" name="password" icon="lock" placeholder="••••••••" required autocomplete="current-password" autofocus
                         :class="$errors->has('password') ? 'input-error' : ''" />
            </x-field>

            @if($errors->any())
                <div class="flex items-center gap-2.5 rounded-xl bg-rose-50 px-3.5 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20" role="alert">
                    <x-icon name="alert-circle" class="size-4.5 shrink-0" />
                    {{ $errors->first() }}
                </div>
            @endif

            <x-btn type="submit" class="w-full" size="lg" icon="shield-check">
                تأیید و ادامه
            </x-btn>
        </form>

        @if (\Illuminate\Support\Facades\Route::has('password.request'))
            <p class="mt-5 text-center text-sm">
                <a href="{{ route('password.request') }}" class="link">گذرواژه خود را فراموش کرده‌اید؟</a>
            </p>
        @endif
    </div>
</x-layouts.guest>
