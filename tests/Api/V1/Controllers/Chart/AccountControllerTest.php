<?php
/**
 * AccountControllerTest.php
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

namespace Tests\Api\V1\Controllers\Chart;


use Amount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccountControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AccountController
     * @covers \FireflyIII\Api\V1\Requests\DateRequest
     */
    public function testOverview(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $asset         = $this->getRandomAsset();
        $euro          = $this->getEuro();
        // mock calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET]])->atLeast()->once()->andReturn(new Collection([$asset]));
        $repository->shouldReceive('getAccountsById')->withArgs([[$asset->id]])->atLeast()->once()->andReturn(new Collection([$asset]));
        $repository->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);

        // mock Steam:
        Steam::shouldReceive('balanceInRange')->atLeast()->once()->andReturn(['2019-01-01' => '-123',]);

        // mock Preferences:
        $preference       = new Preference;
        $preference->data = [$asset->id];
        Preferences::shouldReceive('get')->withArgs(['frontPageAccounts', [$asset->id]])->andReturn($preference);

        // mock Amount
        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);


        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.account.overview') . '?' . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AccountController
     * @covers \FireflyIII\Api\V1\Requests\DateRequest
     */
    public function testRevenueOverview(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $revenue       = $this->getRandomRevenue();
        $euro          = $this->getEuro();
        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::REVENUE]])
                   ->atLeast()->once()->andReturn(new Collection([$revenue]));
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()->withArgs([1])->andReturn($euro);

        // mock Steam, first start and then end.
        $startBalances = [
            $revenue->id => [
                1 => '10',
            ],
        ];
        $endBalances   = [
            $revenue->id => [
                1 => '20',
            ],
        ];

        Steam::shouldReceive('balancesPerCurrencyByAccounts')->times(2)
             ->andReturn($startBalances, $endBalances);

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.account.revenue') . '?' . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AccountController
     */
    public function testExpenseOverview(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $expense = $this->getRandomExpense();
        $euro    = $this->getEuro();
        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::EXPENSE]])
                   ->atLeast()->once()->andReturn(new Collection([$expense]));
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()->withArgs([1])->andReturn($euro);

        // mock Steam, first start and then end.
        $startBalances = [
            $expense->id => [
                1 => '-10',
            ],
        ];
        $endBalances   = [
            $expense->id => [
                1 => '-20',
            ],
        ];

        Steam::shouldReceive('balancesPerCurrencyByAccounts')->times(2)
             ->andReturn($startBalances, $endBalances);

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.account.expense') . '?' . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }


}
