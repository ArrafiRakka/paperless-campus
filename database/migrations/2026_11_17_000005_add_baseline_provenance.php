<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->text('baseline_source')->nullable();
            $table->date('baseline_effective_at')->nullable();
        });
        \Illuminate\Support\Facades\DB::table('document_types')->whereNull('baseline_source')->update([
            'baseline_source' => 'Asumsi demo; belum divalidasi melalui observasi unit.',
            'baseline_effective_at' => date('Y-m-d'),
        ]);
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropColumn(['baseline_source', 'baseline_effective_at']);
        });
    }
};
