<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Submission::query()->with(['user:id,name,email', 'documentType:id,name,code'])->whereIn('status', ['submitted', 'in_review'])->latest('submitted_at');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('registration_number', 'like', '%'.$request->string('q')->toString().'%')->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->string('q')->toString().'%')));

        return view('reviews.index', ['submissions' => $query->paginate(15)->withQueryString()]);
    }

    public function show(Submission $submission): View
    {
        Gate::authorize('view', $submission);

        return view('reviews.show', ['submission' => $submission->load(['user', 'documentType', 'attachments', 'approvals.verifier', 'events.actor'])]);
    }

    public function decide(Request $request, Submission $submission, string $action, SubmissionWorkflow $workflow): RedirectResponse
    {
        abort_unless(in_array($action, ['approved', 'revision_requested', 'rejected'], true), 404);
        Gate::authorize('decide', $submission);
        $validated = $request->validate([
            'step_number' => ['required', 'integer', 'min:1'],
            'note' => [$action === 'approved' ? 'nullable' : 'required', 'string', 'max:2000'],
        ]);
        $workflow->decide($submission, $request->user(), $action, $validated['note'] ?? null, (int) $validated['step_number']);

        return redirect()->route('reviews.show', $submission)->with('success', match ($action) { 'approved' => 'Persetujuan tahap berhasil dicatat.', 'revision_requested' => 'Permintaan revisi berhasil dikirim.', default => 'Penolakan berhasil dicatat.' });
    }
}
