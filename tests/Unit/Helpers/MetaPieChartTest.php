<?php
/**
 * MetaPieChartTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\Helpers;


use Carbon\Carbon;
use FireflyIII\Helpers\Chart\MetaPieChart;
use Tests\TestCase;

class MetaPieChartTest extends TestCase
{
    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::__construct
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::generate
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::getTransactions
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::groupByFields
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::organizeByType
     */
    public function testGenerateIncomeAccount()
    {
        $som = (new Carbon())->startOfMonth();
        $eom = (new Carbon())->endOfMonth();

        $helper = new MetaPieChart();
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $chart = $helper->generate('income', 'account');
        $this->assertTrue(true);
    }

}