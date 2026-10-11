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
        Schema::create('work_programs', function (Blueprint $table) {
            $table->id();
            // organization_id is copied from the period on creation and never changes.
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_id', 'status']);
            $table->index(['organization_id', 'period_id']);
            $table->index('pic_user_id');
        });

        Schema::create('indicator_work_program', function (Blueprint $table) {
            $table->foreignId('work_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->restrictOnDelete();

            $table->primary(['work_program_id', 'indicator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_work_program');
        Schema::dropIfExists('work_programs');
    }
};
