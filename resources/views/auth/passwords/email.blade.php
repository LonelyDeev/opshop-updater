<x-layouts.guest title="بازیابی گذرواژه">
    <div class="card animate-slide-up bg-white/95 p-6 backdrop-blur dark:bg-zinc-900/95 sm:p-8">
        <p class="mb-6 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
            ایمیل خود را وارد کنید تا لینک بازیابی گذرواژه برای شما ارسال شود.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <x-field label="ایمیل" for="email" required>
                <x-input id="email" type="email" name="email" :value="old('email')" icon="mail" placeholder="you@example.com" required autofocus
                         :class="$errors->has('email') ? 'input-error' : ''" />
            </x-field>

            @if($errors->any())
                <div class="flex items-center gap-2.5 rounded-xl bg-rose-50 px-3.5 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20" role="alert">
                    <x-icon name="alert-circle" class="size-4.5 shrink-0" />
                    {{ $errors->first() }}
                </div>
            @endif

            <x-btn type="submit" class="w-full" size="lg" icon="send">
                ارسال لینک بازیابی
            </x-btn>
        </form>

        <p class="mt-5 text-center text-sm">
            <a href="{{ route('login') }}" class="link">بازگشت به ورود</a>
        </p>
    </div>
</x-layouts.guest>
