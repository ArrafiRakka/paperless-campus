<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $query = Submission::query()->with(['user:id,name', 'documentType:id,name,code', 'generatedDocument'])->where('status', 'completed')->latest('completed_at');
        if ($request->user()->isRole('pengaju')) $query->where('user_id', $request->user()->id);
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($q) => $q->where('registration_number', 'like', '%'.$term.'%')->orWhereHas('generatedDocument', fn ($d) => $d->where('document_number', 'like', '%'.$term.'%'))->when($request->user()->isRole('admin', 'verifikator'), fn ($q) => $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$term.'%'))));
        }
        if ($request->filled('type')) $query->whereHas('documentType', fn ($q) => $q->where('code', $request->string('type')->toString()));

        return view('archive.index', ['submissions' => $query->paginate(15)->withQueryString(), 'types' => DocumentType::query()->where('is_active', true)->get()]);
    }

    public function download(Submission $submission)
    {
        Gate::authorize('download', $submission);
        $document = $submission->generatedDocument;
        abort_unless($document && Storage::disk('local')->exists($document->pdf_path), 404, 'Dokumen final belum tersedia.');

        return Storage::disk('local')->download($document->pdf_path, $document->document_number.'.pdf', ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}
