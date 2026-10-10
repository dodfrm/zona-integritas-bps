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
        // A guideline version is a snapshot of areas & indicators. Published versions are read-only;
        // changes are made in a new (copied) version so older periods keep their definitions.
        Schema::create('guideline_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('copied_from_id')->nullable()->constrained('guideline_versions')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guideline_version_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['guideline_version_id', 'code']);
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('indicators')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->text('evidence_guidance')->nullable();
            // Stored for future use only; no scoring formula is applied until officially confirmed.
            $table->decimal('weight', 8, 4)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('guideline_versions');
    }
};
