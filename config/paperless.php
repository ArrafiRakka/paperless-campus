<?php

return [
    'emissions' => [
        'paper_mass_g' => 4.99,
        'paper_kg_co2e_per_kg' => 1.33932,
        'print_kwh_per_page' => 0.001,
        'grid_kg_co2e_per_kwh' => 0.87,
        'motor_kg_co2_per_litre' => 2.2565,
        'motor_km_per_litre' => 45,
        'motor_share' => 0.30,
        'trip_km_round_trip' => 2,
        'residual_share' => 0.10,
        'page_mb' => 0.35,
        'pdf_mb' => 0.15,
        'visits' => 12,
        'operational_kwh_per_gb' => 0.194,
        'embodied_kwh_per_gb' => 0.106,
        'global_embodied_kg_co2e_per_kwh' => 0.494,
    ],
    'cost' => ['paper_per_sheet' => 110, 'printing_per_page' => 200],
    'demo' => ['enabled' => env('PAPERLESS_DEMO_SEED', true)],
];
