<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SavingsService
{
    public function __construct(private readonly EmissionCalculator $calculator) {}

    public function dashboard(?int $userId = null): array
    {
        $key = 'paperless.impact.'.($userId ? 'user.'.$userId : 'unit');

        return Cache::remember($key, now()->addMinutes(10), function () use ($userId): array {
            $types = DocumentType::query()->orderBy('id')->get(['id', 'code', 'name', 'estimated_sheets', 'estimated_trips']);
            $counts = Submission::query()->where('status', 'completed')->when($userId, fn ($query) => $query->where('user_id', $userId))
                ->selectRaw('document_type_id, COUNT(*) as documents')->groupBy('document_type_id')->pluck('documents', 'document_type_id');
            $factors = array_replace(config('paperless.emissions'), \App\Models\EmissionFactor::query()->pluck('value', 'code')->map(fn ($value) => (float) $value)->all());
            $paperCost = (float) ($factors['paper_cost_per_sheet'] ?? config('paperless.cost.paper_per_sheet'));
            $printingCost = (float) ($factors['printing_cost_per_page'] ?? config('paperless.cost.printing_per_page'));
            $total = ['documents' => 0, 'sheets' => 0, 'trips' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
            $byType = [];
            $trend = [];
            foreach (range(5, 0) as $offset) {
                $monthDate = now()->startOfMonth()->subMonths($offset);
                $trend[$monthDate->format('Y-m')] = ['month' => $monthDate->translatedFormat('M Y'), 'documents' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
            }

            foreach ($types as $type) {
                $documents = (int) ($counts[$type->id] ?? 0);
                $sheets = (int) $type->estimated_sheets * $documents;
                $trips = (int) $type->estimated_trips * $documents;
                $result = $this->calculator->transaction(array_replace($factors, ['sheets' => (int) $type->estimated_sheets, 'trips' => (int) $type->estimated_trips]));
                $before = $result['before_kg_co2e'] * $documents;
                $after = $result['after_kg_co2e'] * $documents;
                $total['documents'] += $documents;
                $total['sheets'] += $sheets;
                $total['trips'] += $trips;
                $total['before_kg_co2e'] += $before;
                $total['after_kg_co2e'] += $after;
                $byType[] = ['type' => $type->name, 'completed' => $documents, 'sheets' => $sheets, 'before_kg_co2e' => $before, 'after_kg_co2e' => $after];
            }

            foreach ($trend as $key => $month) {
                $monthStart = \Illuminate\Support\Carbon::createFromFormat('Y-m', $key)->startOfMonth();
                $monthCounts = Submission::query()->where('status', 'completed')->whereBetween('completed_at', [$monthStart, $monthStart->copy()->endOfMonth()])
                    ->when($userId, fn ($query) => $query->where('user_id', $userId))
                    ->selectRaw('document_type_id, COUNT(*) as documents')->groupBy('document_type_id')->pluck('documents', 'document_type_id');
                foreach ($types as $type) {
                    $documents = (int) ($monthCounts[$type->id] ?? 0);
                    if ($documents === 0) continue;
                    $result = $this->calculator->transaction(array_replace($factors, ['sheets' => (int) $type->estimated_sheets, 'trips' => (int) $type->estimated_trips]));
                    $trend[$key]['documents'] += $documents;
                    $trend[$key]['before_kg_co2e'] += $result['before_kg_co2e'] * $documents;
                    $trend[$key]['after_kg_co2e'] += $result['after_kg_co2e'] * $documents;
                }
            }

            $total['net_avoided_kg_co2e'] = $total['before_kg_co2e'] - $total['after_kg_co2e'];
            $total['baseline_cost_idr'] = (int) round($total['sheets'] * ($paperCost + $printingCost));
            $total['by_type'] = $byType;
            $total['trend'] = array_values($trend);

            return $total;
        });
    }

    public function forget(): void
    {
        Cache::forget('paperless.impact.unit');
        foreach (User::query()->pluck('id') as $id) Cache::forget('paperless.impact.user.'.$id);
    }
}
