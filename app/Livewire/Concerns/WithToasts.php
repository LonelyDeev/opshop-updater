<?php

namespace App\Livewire\Concerns;

/**
 * Shared toast dispatcher for Livewire components.
 *
 *   $this->toast('ذخیره شد');                    // success
 *   $this->toast('خطایی رخ داد', 'error');
 *   $this->toast('توجه!', 'warning');
 */
trait WithToasts
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}
