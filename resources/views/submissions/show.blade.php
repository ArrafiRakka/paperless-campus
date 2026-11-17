@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
    <div class="page-head">
        <div>
            <div class="page-kicker">{{ $submission->registration_number }}</div>
            <h1>{{ $submission->documentType->name }}</h1>
            <p>
                Dibuat {{ $submission->created_at->format('d M Y H:i') }}
                &middot; {{ $submission->user->name }}
            </p>
        </div>
        <a class="button button-quiet" href="{{ route('submissions.index') }}">&larr; Kembali</a>
    </div>

    <div class="detail-layout">
        <section class="card detail-card">
            <div class="detail-overview">
                <div>
                    <div class="subtle-label">
                        Nomor registrasi &middot; {{ $submission->registration_number }}
                    </div>
                    <h2>Informasi pengajuan</h2>
                    <p>{{ $submission->documentType->name }}</p>
                </div>
                <span class="status status-{{ $submission->status === 'completed' ? 'green' : ($submission->status === 'rejected' ? 'red' : ($submission->status === 'revision_requested' ? 'yellow' : 'blue')) }}">
                    {{ [
                        'draft' => 'Draf',
                        'submitted' => 'Diajukan',
                        'in_review' => 'Ditinjau tahap '.$submission->current_step,
                        'revision_requested' => 'Perlu Revisi',
                        'rejected' => 'Ditolak',
                        'completed' => 'Selesai',
                    ][$submission->status] ?? $submission->status }}
                </span>
            </div>

            <div class="info-grid">
                @foreach ($submission->data as $key => $value)
                    <div class="info-block">
                        <span>{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                        <b>{{ is_scalar($value) ? $value : '—' }}</b>
                    </div>
                @endforeach
            </div>

            @if ($submission->attachments->isNotEmpty())
                <div class="timeline">
                    <h3>Lampiran</h3>
                    @foreach ($submission->attachments as $attachment)
                        <p>
                            <a class="text-link" href="{{ route('submissions.attachment', [$submission, $attachment->id]) }}">
                                {{ $attachment->original_name }}
                                &middot; {{ number_format($attachment->size_bytes / 1024, 0) }} KB
                                <span>(Unduh PDF)</span>
                            </a>
                        </p>
                    @endforeach
                </div>
            @endif

            <div class="timeline">
                <h3>Perjalanan pengajuan</h3>

                @foreach ($submission->events as $event)
                    <div class="timeline-item">
                        <b>{{ ucfirst(str_replace('_', ' ', $event->event)) }}</b>
                        <span>
                            {{ $event->occurred_at->format('d M Y H:i') }}
                            &middot; {{ $event->actor?->name ?? 'Sistem' }}
                        </span>
                        @if (data_get($event->metadata, 'note'))
                            <p>{{ data_get($event->metadata, 'note') }}</p>
                        @endif
                    </div>
                @endforeach

                @foreach ($submission->approvals as $approval)
                    <div class="timeline-item">
                        <b>
                            {{ ucfirst(str_replace('_', ' ', $approval->action)) }}
                            &middot; Tahap {{ $approval->step_number }}
                        </b>
                        <span>
                            {{ $approval->acted_at->format('d M Y H:i') }}
                            &middot; {{ $approval->verifier?->name ?? 'Verifikator' }}
                        </span>
                        @if ($approval->note)
                            <p>{{ $approval->note }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="card decision-card">
            <h3>Tindakan</h3>

            @can('update', $submission)
                @if (in_array($submission->status, ['draft', 'revision_requested'], true))
                    <a class="button button-primary" href="{{ route('submissions.edit', $submission) }}">
                        {{ $submission->status === 'draft' ? 'Lanjutkan draf' : 'Perbarui revisi' }}
                    </a>
                @endif

                @if ($submission->status === 'draft')
                    <form method="post" action="{{ route('submissions.submit', $submission) }}" style="margin-top: 10px">
                        @csrf
                        <button class="button button-quiet" type="submit">Kirim draf</button>
                    </form>
                @endif
            @endcan

            @if ($submission->status === 'completed' && $submission->generatedDocument)
                <p>Dokumen internal PDF tersedia. Bukan tanda tangan elektronik tersertifikasi.</p>
                <a class="button button-primary" href="{{ route('archive.download', $submission) }}">
                    Unduh dokumen PDF
                </a>
            @else
                <p>Perkembangan status dan keputusan petugas tampil di timeline.</p>
            @endif
        </aside>
    </div>
@endsection
