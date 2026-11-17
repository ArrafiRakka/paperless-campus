<?php

namespace App\Services;

final class EmissionCalculator
{
    public function transaction(array $input): array
    {
        $sheets = max(0, (int) ($input['sheets'] ?? 0));
        $trips = max(0, (int) ($input['trips'] ?? 0));
        $paperMassKg = $sheets * ((float) ($input['paper_mass_g'] ?? 4.99) / 1000);
        $paperKg = $paperMassKg * (float) ($input['paper_kg_co2e_per_kg'] ?? 1.33932);
        $printKg = $sheets * (float) ($input['print_kwh_per_page'] ?? 0.001) * (float) ($input['grid_kg_co2e_per_kwh'] ?? 0.87);
        $motorPerKm = (float) ($input['motor_kg_co2_per_litre'] ?? 2.2565) / (float) ($input['motor_km_per_litre'] ?? 45);
        $transportKg = $trips * (float) ($input['motor_share'] ?? 0.30) * (float) ($input['trip_km_round_trip'] ?? 2) * $motorPerKm;
        $before = $paperKg + $printKg + $transportKg;
        $residual = $before * (float) ($input['residual_share'] ?? 0.10);
        $visits = max(0, (int) ($input['visits'] ?? 12));
        $submissions = max(0, (int) ($input['submissions'] ?? 1));
        $pageGb = $visits * ((float) ($input['page_mb'] ?? 0.35) / 1000);
        $pdfGb = $submissions * ((float) ($input['pdf_mb'] ?? 0.15) / 1000);
        $digitalKwhPerGb = (float) ($input['operational_kwh_per_gb'] ?? 0.194) * (float) ($input['grid_kg_co2e_per_kwh'] ?? 0.87)
            + (float) ($input['embodied_kwh_per_gb'] ?? 0.106) * (float) ($input['global_embodied_kg_co2e_per_kwh'] ?? 0.494);
        $digitalKg = ($pageGb + $pdfGb) * $digitalKwhPerGb;
        $after = $residual + $digitalKg;
        $net = $before - $after;

        return [
            'sheets' => $sheets,
            'trips' => $trips,
            'paper_kg_co2e' => $paperKg,
            'printing_kg_co2e' => $printKg,
            'transport_kg_co2e' => $transportKg,
            'before_kg_co2e' => $before,
            'residual_kg_co2e' => $residual,
            'digital_data_gb' => $pageGb + $pdfGb,
            'digital_kg_co2e' => $digitalKg,
            'after_kg_co2e' => $after,
            'net_avoided_kg_co2e' => $net,
            'reduction_percent' => $before > 0 ? $net / $before * 100 : 0.0,
        ];
    }

    public function annualScenario(array $monthlyVolumes, array $sheetsByType, array $tripsByType): array
    {
        $submissions = 0;
        $sheets = 0;
        $trips = 0;
        foreach ($monthlyVolumes as $type => $monthly) {
            $annual = max(0, (int) $monthly) * 12;
            $submissions += $annual;
            $sheets += $annual * max(0, (int) ($sheetsByType[$type] ?? 0));
            $trips += $annual * max(0, (int) ($tripsByType[$type] ?? 0));
        }

        return ['submissions' => $submissions, 'sheets' => $sheets, 'trips' => $trips];
    }
}
