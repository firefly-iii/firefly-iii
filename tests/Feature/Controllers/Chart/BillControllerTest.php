<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;


use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BillControllerTest
 *
 * @package Tests\Feature\Controllers\Chart
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BillControllerTest extends TestCase
{
    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BillController::frontpage
     * @covers       \FireflyIII\Http\Controllers\Chart\BillController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $generator  = $this->mock(GeneratorInterface::class);
        $repository = $this->mock(BillRepositoryInterface::class);

        $repository->shouldReceive('getBillsPaidInRange')->once()->andReturn('-1');
        $repository->shouldReceive('getBillsUnpaidInRange')->once()->andReturn('2');
        $generator->shouldReceive('pieChart')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.bill.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BillController::single
     */
    public function testSingle()
    {
        $transaction = factory(Transaction::class)->make();
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(JournalCollectorInterface::class);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setBills')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]))->once();

        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.bill.single', [1]));
        $response->assertStatus(200);
    }

}
