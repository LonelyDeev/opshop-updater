<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تغییر short_description از varchar(255) به TEXT
 * (توضیحات کوتاه حالا می‌تواند حداقل ۱۰۰۰ کلمه / متنی بلند باشد)
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite (محیط توسعه) محدودیت طول رشته ندارد — varchar مثل text عمل می‌کند
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `packages` MODIFY `short_description` TEXT NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `packages` MODIFY `short_description` VARCHAR(255) NULL');
        }
    }
};
