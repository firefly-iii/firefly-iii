<?php
/**
 * JavascriptControllerTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class JavascriptControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JavascriptControllerTest extends TestCase
{
    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController::accounts
     */
    public function testAccounts()
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = factory(Account::class)->make();

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]))
                     ->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once();
        $currencyRepos->shouldReceive('findByCode')->withArgs(['EUR'])->andReturn(new TransactionCurrency);

        $this->be($this->user());
        $response = $this->get(route('javascript.accounts'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController::currencies
     */
    public function testCurrencies()
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $currency   = factory(TransactionCurrency::class)->make();
        $repository->shouldReceive('get')->andReturn(new Collection([$currency]));

        $this->be($this->user());
        $response = $this->get(route('javascript.currencies'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController::variables
     * @covers       \FireflyIII\Http\Controllers\JavascriptController::getDateRangeConfig
     *
     * @param string $range
     *
     * @dataProvider dateRangeProvider
     */
    public function testVariables(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('javascript.variables'));
        $response->assertStatus(200);
    }
}
