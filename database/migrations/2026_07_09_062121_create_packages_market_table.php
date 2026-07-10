<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) جدول اصلی پکیج‌ها (master)
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_free')->default(false);
            $table->unsignedBigInteger('default_price')->default(0);
            $table->string('status', 20)->default('draft');
            $table->string('module_name')->nullable();
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('purchases_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('category');
        });

        // 2) نسخه‌های پکیج (detail)
        Schema::create('package_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('version', 50);
            $table->string('type', 10)->default('patch');
            $table->longText('changelog')->nullable();
            $table->longText('what_added')->nullable();
            $table->longText('what_changed')->nullable();
            $table->longText('what_fixed')->nullable();
            $table->string('file_path');
            $table->string('file_size', 30)->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('min_project_version', 20)->nullable();
            $table->string('min_php_version', 20)->nullable();
            $table->string('min_laravel_version', 20)->nullable();
            $table->json('dependencies')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamp('release_date')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'version']);
            $table->index(['package_id', 'status']);
        });

        // 3) طرح‌های قیمت‌گذاری
        Schema::create('package_pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('duration_months')->default(0);
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount_price')->nullable();
            $table->boolean('is_one_time')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['package_id', 'is_active']);
        });

        // 4) لایسنس‌های صادر شده (بدون ارجاع به package_purchases)
        Schema::create('package_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 64)->unique();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // حذف شد: $table->foreignId('purchase_id')->nullable()->constrained('package_purchases')->nullOnDelete();
            $table->foreignId('renewed_from')->nullable()->constrained('package_licenses')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('duration_months')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'package_id']);
            $table->index('expires_at');
            $table->index('status');
        });

        // 5) تاریخچه خرید (با ارجاع به package_licenses)
        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('package_versions')->nullOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained('package_pricing_plans')->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained('package_licenses')->nullOnDelete();
            $table->string('transaction_id')->nullable()->index();
            $table->string('callback_url')->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('gateway')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['package_id', 'status']);
        });

        // 6) توکن‌های موقت دانلود
        Schema::create('package_download_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('license_id')->constrained('package_licenses')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('package_versions')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });

        Schema::create('package_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('path');              // مسیر در storage/app/public/packages/gallery
            $table->string('original_name')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('size')->nullable(); // bytes
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['package_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_download_tokens');
        Schema::dropIfExists('package_purchases');
        Schema::dropIfExists('package_licenses');
        Schema::dropIfExists('package_pricing_plans');
        Schema::dropIfExists('package_versions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('package_images');

    }
};
