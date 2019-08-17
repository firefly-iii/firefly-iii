<?php
/**
 * MonthReportGeneratorTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Generator\Report\Audit;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\Audit\MonthReportGenerator;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Steam;
use Tests\TestCase;

/**
 *
 * Class MonthReportGeneratorTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MonthReportGeneratorTest extends TestCase
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
     * @covers \FireflyIII\Generator\Report\Audit\MonthReportGenerator
     */
    public function testGetAuditReport(): void
    {
        $asset      = $this->getRandomAsset();
        $date       = new Carbon;
        $start      = Carbon::now()->startOfMonth();
        $end        = Carbon::now()->endOfMonth();
        $collection = new Collection([$asset]);
        $euro       = $this->getEuro();
        $dollar     = $this->getDollar();
        $return     = [
            [
                'description'            => 'Hello',
                'amount'                 => '10',
                'foreign_currency_id'    => null,
                'currency_id'            => $euro->id,
                'source_id'              => $asset->id,
                'source_name'            => $asset->name,
                'transaction_journal_id' => 1,
            ],
            [
                'description'            => 'Hello2',
                'amount'                 => '10',
                'foreign_amount'         => '10',
                'foreign_currency_id'    => $euro->id,
                'currency_id'            => $dollar->id,
                'source_id'              => $asset->id,
                'source_name'            => $asset->name,
                'transaction_journal_id' => 1,

            ],
        ];

        /** @var MonthReportGenerator $generator */
        $generator = app(MonthReportGenerator::class);

        $generator->setStartDate($start);
        $generator->setEndDate($end);
        $generator->setAccounts($collection);

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);

        // mock calls
        Steam::shouldReceive('balance')->times(2)->andReturn('100');
        $accountRepos->shouldReceive('setUser')->once();
        //$accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);

        // mock collector:
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($return);
        //$currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($euro)->once();

        try {
            $result = $generator->getAuditReport($asset, $date);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result['exists']);
        $this->assertEquals('100', $result['endBalance']);
    }
}
