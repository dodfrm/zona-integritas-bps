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
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('work_program_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('action_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 30);
            $table->string('verification_status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'period_id']);
            $table->index(['period_id', 'verification_status']);
            $table->index('uploaded_by');
        });

        // Files are never overwritten: every replacement is a new version row + new stored file.
        Schema::create('evidence_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('disk', 30);
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->text('change_note')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['evidence_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_versions');
        Schema::dropIfExists('evidence');
    }
};
