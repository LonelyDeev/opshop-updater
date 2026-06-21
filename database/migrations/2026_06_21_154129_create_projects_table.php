<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام پروژه
            $table->string('slug')->unique(); // شناسه یکتا برای URL
            $table->text('description')->nullable(); // توضیحات
            $table->string('repository_url')->nullable(); // لینک مخزن (گیتهاب و...)
            $table->enum('status', ['active', 'archived', 'pending'])->default('active'); // وضعیت پروژه
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
