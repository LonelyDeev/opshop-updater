<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_licenses', function (Blueprint $table) {
            $table->foreign('purchase_id')
                ->references('id')
                ->on('package_purchases')
                ->nullOnDelete();
        });

        Schema::table('package_purchases', function (Blueprint $table) {
            $table->foreign('license_id')
                ->references('id')
                ->on('package_licenses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('package_licenses', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
        });

        Schema::table('package_purchases', function (Blueprint $table) {
            $table->dropForeign(['license_id']);
        });
    }
};
