<?php
/**
 * SummaryControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class SummaryControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SummaryControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Also includes NULL currencies for better coverage.
     *
     * @covers \FireflyIII\Api\V1\Controllers\SummaryController
     */
    public function testBasicInTheFuture(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $billRepos     = $this->mock(BillRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);
        $netWorth      = $this->mock(NetWorthInterface::class);
        $abRepos       = $this->mock(AvailableBudgetRepositoryInterface::class);
        $opsRepos      = $this->mock(OperationsRepositoryInterface::class);
        $date          = new Carbon();
        $date->addWeek();

        // data
        $euro         = $this->getEuro();
        $budget       = $this->user()->budgets()->inRandomOrder()->first();
        $account      = $this->getRandomAsset();
        $journals     = [
            [
                'amount'      => '10',
                'currency_id' => 1,
            ],
            [
                'amount'      => '10',
                'currency_id' => 2,
            ],

        ];
        $netWorthData = [
            [
                'currency' => $euro,
                'balance'  => '232',
            ],
            [
                'currency' => $euro,
                'balance'  => '0',
            ],
        ];

        // mock calls.
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $netWorth->shouldReceive('setUser')->atLeast()->once();
        $opsRepos->shouldReceive('setUser')->atLeast()->once();
        $abRepos->shouldReceive('setUser')->atLeast()->once();

        // mock collector calls:
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();

        // used to get balance information (deposits)
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->atLeast()->once()->andReturnSelf();

        // same, but for withdrawals
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->atLeast()->once()->andReturnSelf();

        // system always returns one basic transaction (see above)
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($journals);

        // currency repos does some basic collecting
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn($euro);
        $currencyRepos->shouldReceive('findNull')->withArgs([2])->atLeast()->once()->andReturn(null);

        // bill repository return value
        $billRepos->shouldReceive('getBillsPaidInRangePerCurrency')->atLeast()->once()->andReturn([1 => '123', 2 => '456']);
        $billRepos->shouldReceive('getBillsUnpaidInRangePerCurrency')->atLeast()->once()->andReturn([1 => '123', 2 => '456']);

        // budget repos
        $abRepos->shouldReceive('getAvailableBudgetWithCurrency')->atLeast()->once()->andReturn([1 => '123', 2 => '456']);
        $budgetRepos->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));

        // new stuff
        $opsRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn([]);


        //$opsRepos->shouldReceive('spentInPeriodMc')->atLeast()->once()->andReturn(
//            [
//                [
//                    'currency_id'             => 3,
//                    'currency_code'           => 'EUR',
//                    'currency_symbol'         => 'x',
//                    'currency_decimal_places' => 2,
//                    'amount'                  => 321.21,
//                ],
//                [
//                    'currency_id'             => 1,
//                    'currency_code'           => 'EUR',
//                    'currency_symbol'         => 'x',
//                    'currency_decimal_places' => 2,
//                    'amount'                  => 321.21,
//                ],
//
//            ]
//        );

        // account repos:
        $accountRepos->shouldReceive('getActiveAccountsByType')->atLeast()->once()->withArgs([[AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]])->andReturn(new Collection([$account]));
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true);

        // net worth calculator
        $netWorth->shouldReceive('getNetWorthByCurrency')->atLeast()->once()->andReturn($netWorthData);

        $parameters = [
            'start' => $date->format('Y-m-d'),
            'end'   => $date->addWeek()->format('Y-m-d'),
        ];

        // TODO AFTER 4.8,0: check if JSON is correct
        $response = $this->get(route('api.v1.summary.basic') . '?' . http_build_query($parameters));
        $response->assertStatus(200);
        //$response->assertSee('hi there');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\SummaryController
     */
    public function testBasicInThePast(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $billRepos     = $this->mock(BillRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);
        $netWorth      = $this->mock(NetWorthInterface::class);
        $abRepos       = $this->mock(AvailableBudgetRepositoryInterface::class);
        $opsRepos      = $this->mock(OperationsRepositoryInterface::class);

        // data
        $euro         = $this->getEuro();
        $budget       = $this->user()->budgets()->inRandomOrder()->first();
        $account      = $this->getRandomAsset();
        $journals     = [
            [
                'amount'      => '10',
                'currency_id' => 1,
            ],
        ];
        $netWorthData = [
            [
                'currency' => $euro,
                'balance'  => '232',
            ],
        ];

        // mock calls.
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $netWorth->shouldReceive('setUser')->atLeast()->once();
        $abRepos->shouldReceive('setUser')->atLeast()->once();
        $opsRepos->shouldReceive('setUser')->atLeast()->once();

        // mock collector calls:
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();

        // used to get balance information (deposits)
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->atLeast()->once()->andReturnSelf();

        // same, but for withdrawals
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->atLeast()->once()->andReturnSelf();

        // system always returns one basic transaction (see above)
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($journals);

        // currency repos does some basic collecting
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn($euro);

        // bill repository return value
        $billRepos->shouldReceive('getBillsPaidInRangePerCurrency')->atLeast()->once()->andReturn([1 => '123']);
        $billRepos->shouldReceive('getBillsUnpaidInRangePerCurrency')->atLeast()->once()->andReturn([1 => '123']);

        // budget repos
        $abRepos->shouldReceive('getAvailableBudgetWithCurrency')->atLeast()->once()->andReturn([1 => '123']);
        $budgetRepos->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));

        // new stuff
        $opsRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn([]);

//        $opsRepos->shouldReceive('spentInPeriodMc')->atLeast()->once()->andReturn(
//            [
//                [
//                    'currency_id'             => 1,
//                    'currency_code'           => 'EUR',
//                    'currency_symbol'         => 'x',
//                    'currency_decimal_places' => 2,
//                    'amount'                  => 321.21,
//                ],
//            ]
//        );

        // account repos:
        $accountRepos->shouldReceive('getActiveAccountsByType')->atLeast()->once()->withArgs([[AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]])->andReturn(new Collection([$account]));
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true);

        // net worth calculator
        $netWorth->shouldReceive('getNetWorthByCurrency')->atLeast()->once()->andReturn($netWorthData);

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];

        $response = $this->get(route('api.v1.summary.basic') . '?' . http_build_query($parameters));
        $response->assertStatus(200);
        // TODO AFTER 4.8,0: check if JSON is correct
    }
}
