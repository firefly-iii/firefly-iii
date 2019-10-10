<?php
/**
 * NetWorthTest.php
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

namespace Tests\Unit\Helpers\Report;


use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Report\NetWorth;
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
 * Class NetWorthTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NetWorthTest extends TestCase
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
     * @covers \FireflyIII\Helpers\Report\NetWorth
     */
    public function testGetNetWorthByCurrency(): void
    {
        // variables
        $accounts    = $this->user()->accounts()->take(2)->where('account_type_id', 3)->get();
        $date        = new Carbon('2018-01-01');
        $balanceInfo = [];
        foreach ($accounts as $account) {
            $balanceInfo[$account->id] = '100';
        }

        // mock repositories
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls to facades
        Amount::shouldReceive('getDefaultCurrencyByUser')->once()->andReturn($this->getEuro());
        Steam::shouldReceive('balancesByAccounts')->once()->withAnyArgs()->andReturn($balanceInfo);

        // mock other calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->times(2)->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->times(2)->andReturn('defaultAsset');
        $currencyRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->times(1);


        $helper = new NetWorth();
        $helper->setUser($this->user());
        $result = $helper->getNetWorthByCurrency($accounts, $date);

        $this->assertEquals(200, (int)$result[0]['balance']);
    }

    /**
     * @covers \FireflyIII\Helpers\Report\NetWorth
     */
    public function testGetNetWorthByCurrencyWithCC(): void
    {
        // variables
        $first                    = $this->user()->accounts()->take(1)->where('account_type_id', 3)->first();
        $second                   = $this->user()->accounts()->take(1)->where('account_type_id', 3)->where('id', '!=', $first->id)->first();
        $second->virtualBalance   = '500';
        $date                     = new Carbon('2018-01-01');
        $balanceInfo              = [];
        $balanceInfo[$first->id]  = '100';
        $balanceInfo[$second->id] = '100';

        // mock repositories
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls to facades
        Amount::shouldReceive('getDefaultCurrencyByUser')->once()->andReturn($this->getEuro());
        Steam::shouldReceive('balancesByAccounts')->once()->withAnyArgs()->andReturn($balanceInfo);

        // mock other calls:
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->times(2)->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->times(2)->andReturn('defaultAsset', 'ccAsset');
        $currencyRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->times(1);


        $helper = new NetWorth();
        $helper->setUser($this->user());
        $result = $helper->getNetWorthByCurrency(new Collection([$first, $second]), $date);

        $this->assertEquals(-300, (int)$result[0]['balance']);
    }
}
