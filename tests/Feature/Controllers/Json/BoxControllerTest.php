<?php
/**
 * BoxControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class BoxControllerTest
 */
class BoxControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::available
     */
    public function testAvailable()
    {
        $return     = [
            0 => [
                'spent' => '-1200', // more than budgeted.
            ],
        ];
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::balance
     */
    public function testBalance()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        // try a collector for income:
        /** @var JournalCollectorInterface $collector */
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::bills
     */
    public function testBills()
    {
        $billRepos = $this->mock(BillRepositoryInterface::class);
        $billRepos->shouldReceive('getBillsPaidInRange')->andReturn('0');
        $billRepos->shouldReceive('getBillsUnpaidInRange')->andReturn('0');

        $this->be($this->user());
        $response = $this->get(route('json.box.bills'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::netWorth()
     */
    public function testNetWorth()
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');

        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::netWorth()
     */
    public function testNetWorthFuture()
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');

        $start = new Carbon;
        $start->addMonths(6)->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $this->session(['start' => $start, 'end' => $end]);
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }
}
