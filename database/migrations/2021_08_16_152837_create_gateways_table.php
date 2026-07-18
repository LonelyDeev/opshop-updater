<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatewaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('ordering')->nullable();
            $table->timestamps();

            // 1. ایندکس برای فیلتر درگاه‌های فعال
            $table->index('is_active');

            // 2. ایندکس برای مرتب‌سازی بر اساس ترتیب
            $table->index('ordering');

            // 3. ایندکس ترکیبی برای درگاه‌های فعال با ترتیب
            $table->index(['is_active', 'ordering']);

            // 4. ایندکس برای جستجوی نام درگاه
            $table->index('name');

            // 5. ایندکس برای مرتب‌سازی بر اساس تاریخ
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gateways');
    }
}
