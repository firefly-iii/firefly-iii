<?php
/**
 * BoxControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class BoxControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BoxControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testAvailable(): void
    {
        $this->mockDefaultSession();
        $return        = [
            0 => [
                'spent' => '-1200', // more than budgeted.
            ],
        ];
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $abRepository = $this->mock(AvailableBudgetRepositoryInterface::class);


        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $abRepository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $opsRepository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testAvailableDays(): void
    {
        $this->mockDefaultSession();
        $return        = [
            0 => [
                'spent' => '-800', // more than budgeted.
            ],
        ];
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $abRepository = $this->mock(AvailableBudgetRepositoryInterface::class);

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');


        $abRepository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $opsRepository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBalance(): void
    {
        $this->mockDefaultSession();

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        // try a collector for income:

        //$collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        //$collector->shouldReceive('withOpposingAccount')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([])->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBalanceTransactions(): void
    {
        $withdrawal    = $this->getRandomWithdrawalAsArray();
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $euro          = $this->getEuro();

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        // try a collector for income:

        //$collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        //$collector->shouldReceive('withOpposingAccount')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->atLeast()->once();

        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($euro);

        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBills(): void
    {
        $this->mockDefaultSession();
        $billRepos     = $this->mock(BillRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $billRepos->shouldReceive('getBillsPaidInRange')->andReturn('0');
        $billRepos->shouldReceive('getBillsUnpaidInRange')->andReturn('0');

        $this->be($this->user());
        $response = $this->get(route('json.box.bills'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorth(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];


        $netWorthHelper = $this->mock(NetWorthInterface::class);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn($this->getEuro());
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');


        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthFuture(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn($this->getEuro());
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $start = new Carbon;
        $start->addMonths(6)->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $this->session(['start' => $start, 'end' => $end]);
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthPast(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn($this->getEuro());
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $start = new Carbon;
        $start->subMonths(6)->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $this->session(['start' => $start, 'end' => $end]);
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthNoCurrency(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthNoInclude(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];


        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn($this->getEuro());
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthVirtual(): void
    {
        $this->mockDefaultSession();
        $result = [
            [
                'currency' => $this->getEuro(),
                'balance'  => '3',
            ],
        ];

        $account                  = $this->user()->accounts()->first();
        $account->virtual_balance = '1000';
        $accountRepos             = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos            = $this->mock(CurrencyRepositoryInterface::class);
        Amount::shouldReceive('formatAnything')->andReturn('-100');
        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$account]));
        $currencyRepos->shouldReceive('findNull')->andReturn($this->getEuro());
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }
}
