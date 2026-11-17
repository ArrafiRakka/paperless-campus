@extends('layouts.app')

@section('title', 'Pemeriksaan Pengajuan')

@section('content')
    @php
        $statusLabels = [
            'draft' => 'Draf',
            'submitted' => 'Menunggu verifikasi',
            'in_review' => 'Dalam pemeriksaan',
            'revision_requested' => 'Menunggu revisi pengaju',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai',
        ];
        $statusTone = match ($submission->status) {
            'completed' => 'green',
            'rejected' => 'red',
            'revision_requested' => 'yellow',
            default => 'blue',
        };
    @endphp

    <div class="page-head">
        <div>
            <div class="page-kicker">
                {{ $submission->registration_number }}
                &middot; Tahap {{ $submission->current_step }} dari {{ $submission->documentType->approval_steps }}
            </div>
            <h1>Pemeriksaan Pengajuan</h1>
            <p>{{ $submission->documentType->name }} &middot; {{ $submission->user->name }}</p>
        </div>
        <a class="button button-quiet" href="{{ route('reviews.index') }}">&larr; Antrean</a>
    </div>

    <div class="detail-layout">
        <section class="card detail-card">
            <div class="detail-overview">
                <div>
                    <div class="subtle-label">Status pengajuan</div>
                    <h2>{{ $statusLabels[$submission->status] ?? $submission->status }}</h2>
                    @if ($submission->status === 'completed')
                        <p>Seluruh tahap persetujuan selesai dan dokumen PDF final sudah dibuat.</p>
                    @elseif ($submission->status === 'rejected')
                        <p>Keputusan penolakan sudah dicatat. Pengajuan ini tidak dapat diproses kembali.</p>
                    @elseif ($submission->status === 'revision_requested')
                        <p>Pengajuan menunggu perbaikan dari pemohon sebelum dapat diperiksa kembali.</p>
                    @elseif ($submission->status === 'in_review')
                        <p>Pengajuan sedang diperiksa pada tahap {{ $submission->current_step }}.</p>
                    @endif
                </div>
                <span class="status status-{{ $statusTone }}">{{ $statusLabels[$submission->status] ?? $submission->status }}</span>
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
                            <a href="{{ route('submissions.attachment', [$submission, $attachment->id]) }}" class="text-link">
                                Unduh {{ $attachment->original_name }} &rarr;
                            </a>
                        </p>
                    @endforeach
                </div>
            @endif

            <div class="timeline">
                <h3>Riwayat keputusan</h3>
                @forelse ($submission->approvals as $approval)
                    <div class="timeline-item">
                        <b>{{ ucfirst(str_replace('_', ' ', $approval->action)) }} &middot; Tahap {{ $approval->step_number }}</b>
                        <span>
                            {{ $approval->verifier?->name ?? 'Verifikator' }}
                            &middot; {{ $approval->acted_at->format('d M Y H:i') }}
                        </span>
                        @if ($approval->note)
                            <p>{{ $approval->note }}</p>
                        @endif
                    </div>
                @empty
                    <p>Belum ada keputusan verifikator.</p>
                @endforelse

                @foreach ($submission->events as $event)
                    <div class="timeline-item">
                        <b>{{ ucfirst(str_replace('_', ' ', $event->event)) }}</b>
                        <span>
                            {{ $event->actor?->name ?? 'Sistem' }}
                            &middot; {{ $event->occurred_at->format('d M Y H:i') }}
                        </span>
                        @if (data_get($event->metadata, 'note'))
                            <p>{{ data_get($event->metadata, 'note') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="card decision-card">
            @can('decide', $submission)
                <h3>Catat keputusan</h3>
                <form id="review-form" method="post">
                    @csrf
                    <input type="hidden" name="step_number" value="{{ $submission->current_step }}">
                    <div class="field">
                        <label for="note">Catatan (wajib untuk revisi atau penolakan)</label>
                        <textarea name="note" id="note" maxlength="2000"></textarea>
                    </div>
                    <div class="decision-buttons">
                        <button class="button button-primary" type="submit" formaction="{{ route('reviews.decide', [$submission, 'approved']) }}">
                            Setujui tahap
                        </button>
                        <button class="button button-quiet" type="submit" formaction="{{ route('reviews.decide', [$submission, 'revision_requested']) }}" onclick="document.getElementById('note').required = true">
                            Minta revisi
                        </button>
                        <button class="button button-danger" type="submit" formaction="{{ route('reviews.decide', [$submission, 'rejected']) }}" onclick="document.getElementById('note').required = true">
                            Tolak pengajuan
                        </button>
                    </div>
                </form>
            @else
                <h3>Mode baca saja</h3>
                <p>Keputusan tidak tersedia untuk status pengajuan ini atau akun Anda.</p>
                @if ($submission->status === 'completed' && $submission->generatedDocument)
                    <a class="button button-primary" href="{{ route('archive.download', $submission) }}">Unduh dokumen PDF</a>
                @endif
            @endcan
        </aside>
    </div>
@endsection
