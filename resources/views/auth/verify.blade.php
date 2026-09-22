<x-layouts.guest title="تأیید ایمیل">
    <div class="card animate-slide-up bg-white/95 p-6 backdrop-blur dark:bg-zinc-900/95 sm:p-8">
        @if (session('resent'))
            <div class="mb-6 flex items-center gap-2.5 rounded-xl bg-emerald-50 px-3.5 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/20" role="alert">
                <x-icon name="check-circle" class="size-4.5 shrink-0" />
                لینک تأیید جدید به ایمیل شما ارسال شد.
            </div>
        @endif

        <p class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">
            پیش از ادامه، لطفاً صندوق ایمیل خود را برای لینک تأیید بررسی کنید.
            اگر ایمیل را دریافت نکرده‌اید، می‌توانید دوباره درخواست دهید.
        </p>

        <form method="POST" action="{{ route('verification.resend') }}" class="mt-6">
            @csrf
            <x-btn type="submit" class="w-full" size="lg" icon="send">
                ارسال مجدد لینک تأیید
            </x-btn>
        </form>
    </div>
</x-layouts.guest>
