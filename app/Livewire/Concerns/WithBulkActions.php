<?php

namespace App\Livewire\Concerns;

/**
 * انتخاب چندتایی + حذف گروهی برای صفحات لیست پنل.
 *
 * نحوه استفاده در کامپوننت:
 *   use WithPagination, WithToasts, WithBulkActions;
 *
 *   // متدهای اجباری:
 *   public function bulkPageIds(): array            // آی‌دی‌های صفحه‌ی جاری (برای select-all)
 *   public function deleteSelectedRecords(): void   // حذف رکوردهای انتخاب‌شده + toast خودش
 *
 * نحوه استفاده در Blade:
 *   ردیف:      <input type="checkbox" class="checkbox" wire:model.live="selectedIds" value="{{ $row->id }}">
 *   انتخاب کل: <input type="checkbox" class="checkbox" @if($this->allPageSelected()) checked @endif wire:click="toggleSelectAll">
 *   نوار ابزار: @if($selectedIds) … شمارش + دکمه «حذف گروهی» → wire:click="$set('confirmingBulkDelete', true)" @endif
 *   مودال تأیید: <x-modal wire:model="confirmingBulkDelete" …> → wire:click="bulkDelete"
 *
 * نکته‌ها:
 *  - آی‌دی‌ها به‌صورت رشته نگه داشته می‌شوند (مقدار checkbox ها) — تطبیق دقیق با array_diff.
 *  - انتخاب‌ها با تغییر صفحه پاک نمی‌شوند (می‌توان از چند صفحه انتخاب کرد).
 *  - بعد از حذف، انتخاب‌ها و مودال پاک می‌شوند (clearSelection).
 */
trait WithBulkActions
{
    /** آی‌دی‌های انتخاب‌شده (رشته‌ای) */
    public array $selectedIds = [];

    /** نمایش مودال تأیید حذف گروهی */
    public bool $confirmingBulkDelete = false;

    /** آی‌دی‌های ردیف‌های صفحه‌ی جاری — هر کامپوننت بر اساس records خودش پیاده می‌کند */
    abstract public function bulkPageIds(): array;

    /** حذف رکوردهای انتخاب‌شده (به‌همراه toast) — هر کامپوننت با منطق FK خودش پیاده می‌کند */
    abstract public function deleteSelectedRecords(): void;

    /** آیا همه‌ی ردیف‌های صفحه‌ی جاری انتخاب شده‌اند؟ */
    public function allPageSelected(): bool
    {
        $ids = array_map('strval', $this->bulkPageIds());

        return $ids !== [] && empty(array_diff($ids, $this->selectedIds));
    }

    /** انتخاب/لغو انتخاب همه‌ی ردیف‌های صفحه‌ی جاری */
    public function toggleSelectAll(): void
    {
        $ids = array_map('strval', $this->bulkPageIds());

        if ($ids === []) {
            return;
        }

        // همه انتخاب شده‌اند؟ → حذف فقط آی‌دی‌های همین صفحه از انتخاب‌ها
        if (empty(array_diff($ids, $this->selectedIds))) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    /** پاک کردن انتخاب‌ها و بستن مودال */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->confirmingBulkDelete = false;
    }

    /** اجرای حذف گروهی پس از تأیید */
    public function bulkDelete(): void
    {
        $this->selectedIds = array_values(array_filter($this->selectedIds, 'strlen'));

        if ($this->selectedIds === []) {
            $this->confirmingBulkDelete = false;

            return;
        }

        $this->deleteSelectedRecords();
        $this->clearSelection();
    }
}
