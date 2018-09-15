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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class AutoCompleteControllerTest
 */
class AutoCompleteControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAllAccounts(): void
    {
        // mock stuff
        $accountA     = factory(Account::class)->make();
        $collection   = new Collection([$accountA]);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]])
                     ->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('json.all-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$accountA->name]);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAssetAccounts(): void
    {
        // mock stuff
        $accountA     = factory(Account::class)->make();
        $collection   = new Collection([$accountA]);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('json.asset-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$accountA->name]);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAllTransactionJournals(): void
    {
        $collector = $this->mock(TransactionCollectorInterface::class);
        $collector->shouldReceive('setLimit')->withArgs([250])->andReturnSelf();
        $collector->shouldReceive('setPage')->withArgs([1])->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.all-transaction-journals'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testBills(): void
    {
        $repository = $this->mock(BillRepositoryInterface::class);
        $bills      = factory(Bill::class, 10)->make();

        $repository->shouldReceive('getActiveBills')->andReturn($bills);

        $this->be($this->user());
        $response = $this->get(route('json.bills'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testBudgets(): void
    {
        // mock stuff
        $budget        = factory(Budget::class)->make();
        $categoryRepos = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));
        $this->be($this->user());
        $response = $this->get(route('json.budgets'));
        $response->assertStatus(200);
        $response->assertExactJson([$budget->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testCategories(): void
    {
        // mock stuff
        $category      = factory(Category::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $this->be($this->user());
        $response = $this->get(route('json.categories'));
        $response->assertStatus(200);
        $response->assertExactJson([$category->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testCurrencyNames(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);

        $currency = TransactionCurrency::find(1);
        $repository->shouldReceive('get')->andReturn(new Collection([$currency]))->once();

        $this->be($this->user());
        $response = $this->get(route('json.currency-names'));
        $response->assertStatus(200);
        $response->assertExactJson(['Euro']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testExpenseAccounts(): void
    {
        // mock stuff
        $accountA         = factory(Account::class)->make();
        $accountB         = factory(Account::class)->make();
        $accountA->active = true;
        $accountB->active = false;
        $collection       = new Collection([$accountA, $accountB]);
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::EXPENSE, AccountType::BENEFICIARY]])->once()->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('json.expense-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$accountA->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testJournalsWithId(): void
    {
        $journal             = $this->user()->transactionJournals()->where('id', '!=', 1)->first();
        $journal->journal_id = $journal->id;
        $collection          = new Collection([$journal]);
        $collector           = $this->mock(TransactionCollectorInterface::class);
        $collector->shouldReceive('setLimit')->withArgs([400])->andReturnSelf();
        $collector->shouldReceive('setPage')->withArgs([1])->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('json.journals-with-id', [1]));
        $response->assertStatus(200);
        $response->assertExactJson([['id' => $journal->id, 'name' => $journal->id . ': ' . $journal->description]]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testRevenueAccounts(): void
    {
        // mock stuff
        $accountA         = factory(Account::class)->make();
        $accountB         = factory(Account::class)->make();
        $accountA->active = true;
        $accountB->active = false;
        $collection       = new Collection([$accountA, $accountB]);
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::REVENUE]])->once()->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('json.revenue-accounts'));
        $response->assertStatus(200);
        $response->assertExactJson([$accountA->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testTags(): void
    {
        // mock stuff
        $tag          = factory(Tag::class)->make();
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]))->once();

        $this->be($this->user());
        $response = $this->get(route('json.tags'));
        $response->assertStatus(200);
        $response->assertExactJson([$tag->tag]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testTransactionJournals(): void
    {
        // mock stuff
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.transaction-journals', ['deposit']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testTransactionTypes(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getTransactionTypes')->once()->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.transaction-types', ['deposit']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }
}
