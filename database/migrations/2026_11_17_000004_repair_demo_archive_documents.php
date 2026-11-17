<?php

use App\Models\GeneratedDocument;
use App\Services\MinimalPdfGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        GeneratedDocument::query()->with('submission.documentType', 'submission.user')->get()->each(function (GeneratedDocument $document): void {
            if (strtolower(pathinfo($document->pdf_path, PATHINFO_EXTENSION)) === 'pdf') return;
            $submission = $document->submission;
            if (! $submission || ! $submission->completed_at || ! Storage::disk('local')->exists($document->pdf_path)) return;
            $lines = ['PaperLess Campus - Dokumen Internal', 'Nomor: '.$document->document_number, 'Registrasi: '.$submission->registration_number, 'Layanan: '.$submission->documentType->name, 'Pemohon: '.$submission->user->name, 'Dokumen simulasi, bukan tanda tangan elektronik tersertifikasi.'];
            $contents = app(MinimalPdfGenerator::class)->make($lines);
            $path = 'generated/'.$submission->id.'/'.$document->document_number.'.pdf';
            Storage::disk('local')->put($path, $contents);
            $document->forceFill(['pdf_path' => $path, 'sha256' => hash('sha256', $contents)])->save();
        });
    }

    public function down(): void
    {
        // Archived files are retained; no destructive reverse conversion is performed.
    }
};
