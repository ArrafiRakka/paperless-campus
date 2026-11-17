<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\GeneratedDocument;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionWorkflow
{
    public function __construct(private readonly SavingsService $savings, private readonly MinimalPdfGenerator $pdfGenerator) {}

    public function create(User $user, int $typeId, array $data, bool $submit): Submission
    {
        return DB::transaction(function () use ($user, $typeId, $data, $submit): Submission {
            do {
                $registration = 'PLC-'.now()->format('ymd').'-'.strtoupper(Str::random(8));
            } while (Submission::query()->where('registration_number', $registration)->exists());
            $submission = Submission::create([
                'registration_number' => $registration,
                'user_id' => $user->id,
                'document_type_id' => $typeId,
                'data' => $data,
                'purpose' => $data['purpose'] ?? null,
                'status' => $submit ? 'submitted' : 'draft',
                'current_step' => 1,
                'submitted_at' => $submit ? now() : null,
            ]);
            $submission->events()->create(['actor_id' => $user->id, 'event' => $submit ? 'submitted' : 'draft_created', 'metadata' => [], 'occurred_at' => now()]);

            return $submission;
        });
    }

    public function submit(Submission $submission, User $user): void
    {
        $this->resubmitDraft($submission, $user);
    }

    public function revise(Submission $submission, User $user, array $data): void
    {
        abort_unless($submission->status === 'revision_requested', 422, 'Pengajuan ini tidak sedang menunggu revisi.');
        DB::transaction(function () use ($submission, $user, $data): void {
            $submission->forceFill(['data' => $data, 'purpose' => $data['purpose'] ?? null, 'status' => 'submitted', 'submitted_at' => now()])->save();
            $submission->events()->create(['actor_id' => $user->id, 'event' => 'revised_and_submitted', 'metadata' => [], 'occurred_at' => now()]);
        });
    }

    public function resubmitDraft(Submission $submission, User $user): void
    {
        abort_unless($submission->status === 'draft', 422, 'Hanya draf yang dapat dikirim.');
        DB::transaction(function () use ($submission, $user): void {
            $locked = Submission::query()->lockForUpdate()->findOrFail($submission->id);
            abort_unless($locked->status === 'draft', 409, 'Status draf sudah berubah.');
            $locked->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
            $locked->events()->create(['actor_id' => $user->id, 'event' => 'submitted', 'metadata' => [], 'occurred_at' => now()]);
        });
    }

    public function decide(Submission $submission, User $verifier, string $action, ?string $note): void
    {
        abort_unless(in_array($action, ['approved', 'revision_requested', 'rejected'], true), 422);
        if ($action !== 'approved') abort_unless(filled($note), 422, 'Catatan wajib diisi.');

        DB::transaction(function () use ($submission, $verifier, $action, $note): void {
            $locked = Submission::query()->with('documentType')->lockForUpdate()->findOrFail($submission->id);
            abort_unless(in_array($locked->status, ['submitted', 'in_review'], true), 409, 'Status pengajuan telah berubah.');
            $step = $locked->current_step;
            $lastAtStep = $locked->approvals()->where('step_number', $step)->latest('acted_at')->first();
            abort_if($lastAtStep && $lastAtStep->action !== 'revision_requested', 409, 'Tahap ini sudah diputuskan.');
            ApprovalAction::create(['submission_id' => $locked->id, 'verifier_id' => $verifier->id, 'step_number' => $step, 'action' => $action, 'note' => $note, 'acted_at' => now()]);
            $locked->events()->create(['actor_id' => $verifier->id, 'event' => $action, 'metadata' => ['step' => $step, 'note' => $note], 'occurred_at' => now()]);

            if ($action === 'approved' && $step < $locked->documentType->approval_steps) {
                $locked->forceFill(['status' => 'in_review', 'current_step' => $step + 1])->save();
                return;
            }
            if ($action === 'approved') {
                $locked->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
                $this->generateDocumentOnce($locked);
                $this->savings->forget();
                return;
            }
            $locked->forceFill(['status' => $action])->save();
        });
    }

    private function generateDocumentOnce(Submission $submission): void
    {
        if (GeneratedDocument::query()->where('submission_id', $submission->id)->exists()) return;
        $number = 'DOC-'.now()->format('Y').'-'.str_pad((string) $submission->id, 6, '0', STR_PAD_LEFT);
        $submission->load('user', 'documentType');
        $lines = [
            'PaperLess Campus - Dokumen Internal',
            'Nomor: '.$number,
            'Registrasi: '.$submission->registration_number,
            'Layanan: '.$submission->documentType->name,
            'Pemohon: '.$submission->user->name,
            'Tanggal selesai: '.now()->format('d-m-Y H:i'),
            'Data permohonan:',
        ];
        foreach ($submission->data as $key => $value) if (is_scalar($value)) $lines[] = ucfirst(str_replace('_', ' ', $key)).': '.$value;
        $lines[] = 'Dokumen simulasi administrasi. Bukan tanda tangan elektronik tersertifikasi.';
        $contents = $this->pdfGenerator->make($lines);
        $path = 'generated/'.$submission->id.'/'.$number.'.pdf';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $contents);
        GeneratedDocument::create(['submission_id' => $submission->id, 'document_number' => $number, 'pdf_path' => $path, 'sha256' => hash('sha256', $contents), 'generated_at' => now()]);
        $submission->events()->create(['actor_id' => null, 'event' => 'document_generated', 'metadata' => ['document_number' => $number], 'occurred_at' => now()]);
    }
}
