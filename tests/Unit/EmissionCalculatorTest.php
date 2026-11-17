<?php

namespace Tests\Unit;

use App\Services\EmissionCalculator;
use Tests\TestCase;

class EmissionCalculatorTest extends TestCase
{
    public function test_annual_scenario_volumes_and_reference_sanity_values(): void
    {
        $calculator=new EmissionCalculator();
        $annual=$calculator->annualScenario(['active'=>150,'event'=>40,'facility'=>60],['active'=>3,'event'=>10,'facility'=>4],['active'=>2,'event'=>4,'facility'=>3]);
        $this->assertSame(['submissions'=>3000,'sheets'=>13080,'trips'=>7680],$annual);
        $input=array_merge(config('paperless.emissions'),['sheets'=>$annual['sheets'],'trips'=>$annual['trips'],'submissions'=>3000,'visits'=>3000*12+20*240]);
        $result=$calculator->transaction($input);
        $this->assertEqualsWithDelta(329.86,$result['before_kg_co2e'],1.5);
        $this->assertEqualsWithDelta(3.26,$result['digital_kg_co2e'],1.0);
        $this->assertEqualsWithDelta(36.24,$result['after_kg_co2e'],1.5);
        $this->assertEqualsWithDelta(89,$result['reduction_percent'],3);
    }

    public function test_zero_baseline_has_no_division_by_zero_and_factors_change_output(): void
    {
        $calculator=new EmissionCalculator();
        $zero=$calculator->transaction(['sheets'=>0,'trips'=>0,'submissions'=>0,'visits'=>0]);
        $this->assertSame(0.0,$zero['reduction_percent']);
        $first=$calculator->transaction(['sheets'=>10,'trips'=>0,'page_mb'=>0.35]);
        $second=$calculator->transaction(['sheets'=>10,'trips'=>0,'page_mb'=>0.7]);
        $this->assertGreaterThan($first['digital_kg_co2e'],$second['digital_kg_co2e']);
    }
}
