<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'submitted_at']);
            $table->index(['status', 'submitted_at', 'completed_at']);
        });
        Schema::table('emission_factors', function (Blueprint $table): void {
            $table->decimal('value', 14, 7)->change();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'submitted_at', 'completed_at']);
            $table->index(['status', 'submitted_at']);
        });
    }
};
