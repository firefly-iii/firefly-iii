<?php
/**
 * AutoCompleteControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class AutoCompleteControllerTest
 *
 * @package Tests\Feature\Controllers\Json
 */
class AutoCompleteControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController::allAccounts
     */
    public function testAllAccounts()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]])
                     ->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.all-accounts'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController::expenseAccounts
     */
    public function testExpenseAccounts()
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::EXPENSE, AccountType::BENEFICIARY]])->once()->andReturn(
            new Collection([$account])
        );

        $this->be($this->user());
        $response = $this->get(route('json.expense-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$account->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController::revenueAccounts
     */
    public function testRevenueAccounts()
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::REVENUE]])->once()->andReturn(
            new Collection([$account])
        );

        $this->be($this->user());
        $response = $this->get(route('json.revenue-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$account->name]);
    }

}
