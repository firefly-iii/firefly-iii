<?php
/**
 * AutoCompleteControllerTest.php
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

namespace Tests\Feature\Controllers\Json;


use FireflyIII\Helpers\Collector\JournalCollectorInterface;
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
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController::allTransactionJournals
     */
    public function testAllTransactionJournals()
    {
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setLimit')->withArgs([250])->andReturnSelf();
        $collector->shouldReceive('setPage')->withArgs([1])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.all-transaction-journals'));
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

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController::transactionJournals
     */
    public function testTransactionJournals()
    {
        // mock stuff
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.transaction-journals', ['deposit']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

}
