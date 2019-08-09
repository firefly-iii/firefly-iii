<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class BillControllerTest
 */
class BillControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BillController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range): void
    {
        $generator     = $this->mock(GeneratorInterface::class);
        $repository    = $this->mock(BillRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $amounts = [
            1 => '100',
            2 => '100',
        ];

        $currencyRepos->shouldReceive('findNull')->once()->andReturn($this->getEuro())->withArgs([1]);
        $currencyRepos->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(2))->withArgs([2]);

        $repository->shouldReceive('getBillsPaidInRangePerCurrency')->once()->andReturn($amounts);
        $repository->shouldReceive('getBillsUnpaidInRangePerCurrency')->once()->andReturn($amounts);
        $generator->shouldReceive('multiCurrencyPieChart')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.bill.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BillController
     */
    public function testSingle(): void
    {
        $withdrawal = $this->getRandomWithdrawalAsArray();
        $generator  = $this->mock(GeneratorInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $collector->shouldReceive('setBill')->andReturnSelf()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->once();
        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.bill.single', [1]));
        $response->assertStatus(200);
    }
}
