<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\Submission;
use Illuminate\Support\Facades\Cache;

class SavingsService
{
    public function __construct(private readonly EmissionCalculator $calculator) {}

    public function dashboard(?int $userId = null): array
    {
        $key = 'paperless.impact.'.($userId ? 'user.'.$userId : 'unit');

        return Cache::remember($key, now()->addMinutes(10), function () use ($userId): array {
            $query = Submission::query()->with('documentType:id,code,name,estimated_sheets,estimated_trips')
                ->where('status', 'completed');
            if ($userId) $query->where('user_id', $userId);
            $completed = $query->get(['id', 'document_type_id', 'user_id', 'data', 'completed_at']);
            $factors = config('paperless.emissions');
            $total = ['documents' => $completed->count(), 'sheets' => 0, 'trips' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
            $factors = array_replace($factors, \App\Models\EmissionFactor::query()->pluck('value', 'code')->map(fn ($value) => (float) $value)->all());
            $paperCost = (float) ($factors['paper_cost_per_sheet'] ?? config('paperless.cost.paper_per_sheet'));
            $printingCost = (float) ($factors['printing_cost_per_page'] ?? config('paperless.cost.printing_per_page'));
            $byType = [];
            $trend = [];
            foreach (range(5, 0) as $offset) {
                $monthDate = now()->startOfMonth()->subMonths($offset);
                $trend[$monthDate->format('Y-m')] = ['month' => $monthDate->translatedFormat('M Y'), 'documents' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
            }
            foreach ($completed as $submission) {
                $sheets = (int) $submission->documentType->estimated_sheets;
                $trips = (int) $submission->documentType->estimated_trips;
                $result = $this->calculator->transaction(array_merge($factors, ['sheets' => $sheets, 'trips' => $trips]));
                $total['sheets'] += $sheets;
                $total['trips'] += $trips;
                $total['before_kg_co2e'] += $result['before_kg_co2e'];
                $total['after_kg_co2e'] += $result['after_kg_co2e'];
                $code = $submission->documentType->code;
                $byType[$code] ??= ['type' => $submission->documentType->name, 'completed' => 0, 'sheets' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
                $byType[$code]['completed']++;
                $byType[$code]['sheets'] += $sheets;
                $byType[$code]['before_kg_co2e'] += $result['before_kg_co2e'];
                $byType[$code]['after_kg_co2e'] += $result['after_kg_co2e'];
                if ($submission->completed_at) {
                    $month = $submission->completed_at->format('Y-m');
                    if (isset($trend[$month])) {
                        $trend[$month]['documents']++;
                        $trend[$month]['before_kg_co2e'] += $result['before_kg_co2e'];
                        $trend[$month]['after_kg_co2e'] += $result['after_kg_co2e'];
                    }
                }
            }
            $total['net_avoided_kg_co2e'] = $total['before_kg_co2e'] - $total['after_kg_co2e'];
            $total['baseline_cost_idr'] = (int) round($total['sheets'] * ($paperCost + $printingCost));
            $total['by_type'] = DocumentType::query()->get(['id', 'code', 'name'])->map(function (DocumentType $type) use ($byType): array {
                return $byType[$type->code] ?? ['type' => $type->name, 'completed' => 0, 'sheets' => 0, 'before_kg_co2e' => 0.0, 'after_kg_co2e' => 0.0];
            })->all();
            $total['trend'] = array_values(array_slice($trend, -6, null, true));

            return $total;
        });
    }

    public function forget(): void
    {
        Cache::forget('paperless.impact.unit');
        foreach (\App\Models\User::query()->pluck('id') as $id) Cache::forget('paperless.impact.user.'.$id);
    }
}
