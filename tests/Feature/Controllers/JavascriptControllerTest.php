<?php
/**
 * JavascriptControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * @package Tests\Feature\Controllers
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
     * @covers       \FireflyIII\Http\Controllers\JavascriptController::getDateRangePicker
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
