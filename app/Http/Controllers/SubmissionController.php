<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Submission;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Submission::query()->with(['documentType:id,name,code', 'generatedDocument'])->latest();
        if ($request->user()->isRole('pengaju')) $query->where('user_id', $request->user()->id);
        if ($request->filled('q')) $query->where('registration_number', 'like', '%'.$request->string('q')->toString().'%');
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());

        return view('submissions.index', ['submissions' => $query->paginate(15)->withQueryString(), 'types' => DocumentType::query()->where('is_active', true)->get()]);
    }

    public function create(): View { return view('submissions.form', ['types' => DocumentType::query()->where('is_active', true)->get(), 'submission' => null]); }

    public function store(Request $request, SubmissionWorkflow $workflow): RedirectResponse
    {
        $type = DocumentType::query()->where('code', $request->input('type'))->where('is_active', true)->firstOrFail();
        $data = $this->validatedData($request, $type->code);
        unset($data['attachment']);
        $submission = $workflow->create($request->user(), $type->id, $data, false);
        if ($request->hasFile('attachment')) $this->storeAttachment($request, $submission);
        if ($request->input('intent') === 'submit') $workflow->submit($submission, $request->user());

        return redirect()->route('submissions.show', $submission)->with('success', $submission->status === 'draft' ? 'Draf berhasil disimpan.' : 'Pengajuan berhasil dikirim.');
    }

    public function show(Request $request, Submission $submission): View
    {
        Gate::authorize('view', $submission);
        $submission->load(['user', 'documentType', 'attachments.uploader', 'approvals.verifier', 'events.actor', 'generatedDocument']);

        return view('submissions.show', compact('submission'));
    }

    public function edit(Submission $submission): View
    {
        Gate::authorize('update', $submission);
        $submission->load('documentType', 'attachments');

        return view('submissions.form', ['types' => DocumentType::query()->whereKey($submission->document_type_id)->get(), 'submission' => $submission]);
    }

    public function update(Request $request, Submission $submission, SubmissionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $submission);
        $type = $submission->documentType;
        $data = $this->validatedData($request, $type->code);
        unset($data['attachment']);
        if ($submission->status === 'revision_requested') {
            $workflow->revise($submission, $request->user(), $data);
            $submission->refresh();
        }
        else $submission->forceFill(['data' => $data, 'purpose' => $data['purpose'] ?? null])->save();
        if ($request->hasFile('attachment')) $this->storeAttachment($request, $submission);
        if ($request->input('intent') === 'submit' && $submission->status === 'draft') $workflow->resubmitDraft($submission, $request->user());

        return redirect()->route('submissions.show', $submission)->with('success', 'Perubahan pengajuan berhasil disimpan.');
    }

    public function submit(Request $request, Submission $submission, SubmissionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $submission);
        $workflow->submit($submission, $request->user());

        return redirect()->route('submissions.show', $submission)->with('success', 'Pengajuan masuk ke antrean verifikasi.');
    }

    public function attachment(Submission $submission, int $attachment)
    {
        Gate::authorize('view', $submission);
        $file = $submission->attachments()->findOrFail($attachment);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name, ['Cache-Control' => 'private, no-store']);
    }

    private function validatedData(Request $request, string $type): array
    {
        $common = ['purpose' => ['required', 'string', 'max:2000']];
        $fields = match ($type) {
            'active' => ['full_name' => ['required', 'string', 'max:160'], 'nim' => ['required', 'string', 'max:40'], 'program' => ['required', 'string', 'max:160'], 'semester' => ['required', 'integer', 'between:1,14']],
            'event' => ['event_name' => ['required', 'string', 'max:180'], 'organizer' => ['required', 'string', 'max:180'], 'event_date' => ['required', 'date', 'after_or_equal:today'], 'location' => ['required', 'string', 'max:180'], 'responsible' => ['required', 'string', 'max:160']],
            'facility' => ['facility' => ['required', 'string', 'max:180'], 'event_name' => ['required', 'string', 'max:180'], 'event_date' => ['required', 'date', 'after_or_equal:today'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i', 'after:start_time'], 'participants' => ['required', 'integer', 'between:1,10000'], 'contact' => ['required', 'string', 'max:40']],
            default => abort(404),
        };
        $rules = array_merge($common, $fields, ['attachment' => ['nullable', 'file', 'mimes:pdf', 'max:2048']]);

        $validated = $request->validate($rules, ['required' => 'Kolom ini wajib diisi.', 'after_or_equal' => 'Tanggal harus hari ini atau setelahnya.', 'after' => 'Waktu selesai harus setelah waktu mulai.', 'mimes' => 'Lampiran harus berupa berkas PDF.', 'max' => 'Lampiran maksimal berukuran 2 MB.']);
        if ($type === 'facility' && $request->input('end_time') <= $request->input('start_time')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        return $validated;
    }

    private function storeAttachment(Request $request, Submission $submission): void
    {
        $file = $request->file('attachment');
        $path = $file->store('attachments/'.$submission->id, 'local');
        $submission->attachments()->create(['uploaded_by' => $request->user()->id, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType() ?: 'application/pdf', 'size_bytes' => $file->getSize()]);
    }
}
