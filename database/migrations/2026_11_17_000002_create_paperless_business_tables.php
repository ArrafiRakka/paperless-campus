<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('estimated_sheets')->default(0);
            $table->unsignedSmallInteger('estimated_trips')->default(0);
            $table->unsignedTinyInteger('approval_steps')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->json('data');
            $table->text('purpose')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'submitted_at']);
            $table->index(['document_type_id', 'status']);
        });

        Schema::create('submission_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->index(['submission_id', 'created_at']);
        });

        Schema::create('approval_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('step_number');
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();
            $table->unique(['submission_id', 'step_number', 'id']);
            $table->index(['submission_id', 'step_number', 'acted_at']);
        });

        Schema::create('submission_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['submission_id', 'occurred_at']);
        });

        Schema::create('generated_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('document_number', 60)->unique();
            $table->string('pdf_path');
            $table->string('sha256', 64)->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('emission_factors', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->decimal('value', 14, 7);
            $table->string('unit', 80);
            $table->string('source')->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('emission_factor_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('emission_factor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('old_value', 14, 7)->nullable();
            $table->decimal('new_value', 14, 7);
            $table->string('note')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_factor_revisions');
        Schema::dropIfExists('emission_factors');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('submission_events');
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('submission_attachments');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('document_types');
    }
};
