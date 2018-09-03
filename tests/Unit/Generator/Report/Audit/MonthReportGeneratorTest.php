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
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
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
 */
class MonthReportGeneratorTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Generator\Report\Audit\MonthReportGenerator
     */
    public function testBasic(): void
    {
        /** @var Account $account */
        $account   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $date      = new Carbon;
        $start     = Carbon::create()->startOfMonth();
        $end       = Carbon::create()->endOfMonth();
        $generator = new MonthReportGenerator();
        $generator->setStartDate($start);
        $generator->setEndDate($end);

        $collection = new Collection;

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(TransactionCollectorInterface::class);
        Steam::shouldReceive('balance')->times(2)->andReturn('100');

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first())->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);


        try {
            $result = $generator->getAuditReport($account, $date);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertFalse($result['exists']);
        $this->assertEquals('100', $result['endBalance']);
    }

    /**
     * @covers \FireflyIII\Generator\Report\Audit\MonthReportGenerator
     */
    public function testBasicNoCurrency(): void
    {
        /** @var Account $account */
        $account   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $date      = new Carbon;
        $start     = Carbon::create()->startOfMonth();
        $end       = Carbon::create()->endOfMonth();
        $generator = new MonthReportGenerator();
        $generator->setStartDate($start);
        $generator->setEndDate($end);

        $collection = new Collection;

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(TransactionCollectorInterface::class);
        Steam::shouldReceive('balance')->times(1)->andReturn('100');

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(null)->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);


        try {
            $generator->getAuditReport($account, $date);
        } catch (FireflyException $e) {
            $this->assertEquals('Unexpected NULL value in account currency preference.', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Generator\Report\Audit\MonthReportGenerator
     */
    public function testBasicWithForeign(): void
    {
        /** @var Account $account */
        $account   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $date      = new Carbon;
        $start     = Carbon::create()->startOfMonth();
        $end       = Carbon::create()->endOfMonth();
        $generator = new MonthReportGenerator();
        $generator->setStartDate($start);
        $generator->setEndDate($end);

        $collection                              = new Collection;
        $transaction                             = $this->user()->transactions()->first();
        $transaction->transaction_amount         = '30';
        $transaction->foreign_currency_id        = 1;
        $transaction->transaction_foreign_amount = '30';
        $collection->push($transaction);

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(TransactionCollectorInterface::class);
        Steam::shouldReceive('balance')->times(2)->andReturn('100');

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first())->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);


        try {
            $result = $generator->getAuditReport($account, $date);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result['exists']);
        $this->assertEquals('100', $result['endBalance']);
    }

    /**
     * @covers \FireflyIII\Generator\Report\Audit\MonthReportGenerator
     */
    public function testBasicWithTransactions(): void
    {
        /** @var Account $account */
        $account   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $date      = new Carbon;
        $start     = Carbon::create()->startOfMonth();
        $end       = Carbon::create()->endOfMonth();
        $generator = new MonthReportGenerator();
        $generator->setStartDate($start);
        $generator->setEndDate($end);

        $collection                      = new Collection;
        $transaction                     = $this->user()->transactions()->first();
        $transaction->transaction_amount = '30';
        $collection->push($transaction);

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(TransactionCollectorInterface::class);
        Steam::shouldReceive('balance')->times(2)->andReturn('100');

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first())->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);


        try {
            $result = $generator->getAuditReport($account, $date);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result['exists']);
        $this->assertEquals('100', $result['endBalance']);
    }

}