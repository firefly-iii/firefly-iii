<?php

namespace Tests\integration\Support;

use Carbon\Carbon;
use FireflyIII\Support\Navigation;
use Tests\integration\TestCase;

class NavigationCustomEndOfPeriodTest extends TestCase
{

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGivenADateAndCustomFrequencyWhenCalculateTheDateThenReturnsTheEndOfMonthSuccessful()
    {
        $from       = Carbon::parse('2023-08-05');
        $expected   = Carbon::parse('2023-09-04');
        $navigation = new Navigation();

        $period = $navigation->endOfPeriod($from, 'custom');
        $this->assertEquals($expected->toDateString(), $period->toDateString());
    }
}
