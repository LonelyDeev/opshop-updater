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
        Schema::create('updates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->index('project_id');
            $table->text('description');
            $table->enum('type', ['major', 'minor', 'patch'])->default('minor');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('download_link')->nullable();
            $table->string('file_size')->nullable();
            $table->timestamp('release_date')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('updates');
    }
};
