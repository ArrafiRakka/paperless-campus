<?php

namespace App\Http\Controllers;

use App\Services\EmissionCalculator;
use App\Services\SavingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImpactController extends Controller
{
    public function index(Request $request, SavingsService $savings, EmissionCalculator $calculator): View
    {
        $userId = $request->user()->isRole('pengaju') ? $request->user()->id : null;
        $actual = $savings->dashboard($userId);
        $annual = $calculator->annualScenario(['active' => 150, 'event' => 40, 'facility' => 60], ['active' => 3, 'event' => 10, 'facility' => 4], ['active' => 2, 'event' => 4, 'facility' => 3]);
        $scenarioInput = array_replace(config('paperless.emissions'), \App\Models\EmissionFactor::query()->pluck('value','code')->map(fn ($v)=>(float)$v)->all());
        $scenarioInput['sheets'] = $annual['sheets'];
        $scenarioInput['trips'] = $annual['trips'];
        $scenarioInput['submissions'] = $annual['submissions'];
        $scenarioInput['visits'] = $annual['submissions'] * 12 + 20 * 240;

        return view('impact.index', ['actual' => $actual, 'scenario' => $calculator->transaction($scenarioInput), 'annual' => $annual, 'isPersonal' => $userId !== null]);
    }

    public function methodology(): View { return view('impact.methodology'); }
}
