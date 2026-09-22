<?php

namespace App\Livewire\Logs;

use App\Livewire\Concerns\WithToasts;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('لاگ‌های سیستم')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    /** error | warning | info | … (هر سطحی که در فایل لاگ وجود داشته باشد) */
    #[Url]
    public string $level = '';

    public bool $confirmClear = false;

    /** تعداد رکورد در هر صفحه */
    protected const PER_PAGE = 25;

    /** @var array<int, array<string, string>>|null */
    protected ?array $parsedCache = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'level');
        $this->resetPage();
    }

    /* ---------------------------------------------------------------- */
    /*  Data                                                             */
    /* ---------------------------------------------------------------- */

    #[Computed]
    public function records(): LengthAwarePaginator
    {
        $entries = $this->parsed();

        // فیلتر بر اساس سطح
        if ($this->level !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry) => strtolower($entry['level']) === strtolower($this->level)
            ));
        }

        // جستجو در تاریخ، سطح و متن پیام
        if ($this->search !== '') {
            $needle = strtolower(trim(en_num($this->search)));
            $entries = array_values(array_filter($entries, function (array $entry) use ($needle) {
                $haystack = strtolower(
                    $entry['date'].' '.$entry['level'].' '.$entry['message'].' '.($entry['context'] ?? '')
                );

                return str_contains($haystack, $needle);
            }));
        }

        // صفحه‌بندی دستی (مجموعه فایل‌محور)
        $page = max((int) LengthAwarePaginator::resolveCurrentPage(), 1);
        $perPage = static::PER_PAGE;
        $items = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($items, count($entries), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /** سطوح موجود در فایل لاگ (برای فیلتر) */
    #[Computed]
    public function levels(): array
    {
        $levels = array_unique(array_map(
            fn (array $entry) => strtolower($entry['level']),
            $this->parsed()
        ));

        sort($levels);

        return $levels;
    }

    #[Computed]
    public function fileSize(): ?int
    {
        return File::exists($this->logPath())
            ? (int) File::size($this->logPath())
            : null;
    }

    /* ---------------------------------------------------------------- */
    /*  Actions (پورت‌شده از LogController)                              */
    /* ---------------------------------------------------------------- */

    /** دانلود فایل لاگ (پورت LogController@download) */
    public function download()
    {
        if (! File::exists($this->logPath())) {
            $this->toast('فایل لاگ یافت نشد.', 'error');

            return null;
        }

        return response()->download($this->logPath(), 'system.log');
    }

    /** پاک کردن کامل فایل لاگ (پورت LogController@clear) */
    public function clear(): void
    {
        if (File::exists($this->logPath())) {
            File::put($this->logPath(), '');
        }

        $this->confirmClear = false;
        $this->parsedCache = null;
        unset($this->records, $this->levels, $this->fileSize);
        $this->resetPage();

        $this->toast('لاگ‌ها با موفقیت پاک شدند.');
    }

    public function render()
    {
        return view('livewire.logs.index');
    }

    /* ---------------------------------------------------------------- */
    /*  Helpers                                                          */
    /* ---------------------------------------------------------------- */

    protected function logPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * تجزیه ساده لاگ‌ها (هر لاگ با تاریخ شروع می‌شود) – پورت‌شده از LogController.
     * الگو: [2024-01-01 12:00:00] production.ERROR: ...
     *
     * @return array<int, array<string, string>>
     */
    protected function parsed(): array
    {
        if ($this->parsedCache !== null) {
            return $this->parsedCache;
        }

        if (! File::exists($this->logPath())) {
            return $this->parsedCache = [];
        }

        $content = (string) File::get($this->logPath());

        preg_match_all(
            '/\[(.*?)\]\s+(\w+)\.(.*?):\s(.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $logs = [];
        foreach ($matches as $match) {
            // اولین خط = پیام، باقی = استک‌تریس/کانتکست
            $lines = preg_split('/\r\n|\r|\n/', trim((string) $match[4])) ?: [];

            $logs[] = [
                'date' => $match[1],
                // [تاریخ] environment.ERROR: ← گروه ۳ = سطح لاگ
                'level' => $match[3],
                'message' => (string) array_shift($lines),
                'context' => trim(implode("\n", $lines)),
            ];
        }

        // نمایش جدیدترین‌ها اول (مطابق کنترلر قدیمی)
        return $this->parsedCache = array_reverse($logs);
    }
}
