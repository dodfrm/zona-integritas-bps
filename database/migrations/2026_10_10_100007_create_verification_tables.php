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
        // Configurable verification flow: an ordered list of stages.
        Schema::create('verification_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->string('executor_level', 20);
            // Optional filter: only evidence of this category goes through the stage (null = all).
            $table->string('evidence_category', 30)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'sequence']);
        });

        // One row per stage per evidence version. Stage name/sequence/level are snapshotted so that
        // later configuration changes never rewrite past decisions.
        Schema::create('evidence_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->foreignId('evidence_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('verification_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage_name');
            $table->unsignedSmallInteger('stage_sequence');
            $table->string('executor_level', 20);
            $table->foreignId('executor_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('status', 30);
            $table->foreignId('assigned_verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['executor_organization_id', 'status']);
            $table->index(['evidence_id', 'stage_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_reviews');
        Schema::dropIfExists('verification_stages');
    }
};
