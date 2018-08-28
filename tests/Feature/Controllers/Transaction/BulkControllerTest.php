<?php
/**
 * BulkControllerTest.php
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

namespace Tests\Feature\Controllers\Transaction;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class BulkControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BulkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     */
    public function testEdit(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Transfer');
        $journalRepos->shouldReceive('isJournalReconciled')->andReturn(false);

        $transfers = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(4)->get()->pluck('id')->toArray();

        $this->be($this->user());
        $response = $this->get(route('transactions.bulk.edit', $transfers));
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     */
    public function testEditMultiple(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalSourceAccounts')
                     ->andReturn(new Collection([1, 2, 3]), new Collection, new Collection, new Collection, new Collection([1]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')
                     ->andReturn(new Collection, new Collection([1, 2, 3]), new Collection, new Collection, new Collection([1]));
        $journalRepos->shouldReceive('getTransactionType')
                     ->andReturn('Withdrawal', 'Opening balance', 'Withdrawal', 'Withdrawal', 'Withdrawal');
        $journalRepos->shouldReceive('isJournalReconciled')
                     ->andReturn(true, false, false, false, false);

        // default transactions
        $collection = $this->user()->transactionJournals()->take(5)->get();
        $allIds     = $collection->pluck('id')->toArray();
        $route      = route('transactions.bulk.edit', implode(',', $allIds));
        $this->be($this->user());
        $response = $this->get($route);
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        $response->assertSessionHas('info');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('marked as reconciled');
        $response->assertSee('multiple source accounts');
        $response->assertSee('multiple destination accounts');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     */
    public function testEditNull(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal, null);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Transfer');
        $journalRepos->shouldReceive('isJournalReconciled')->andReturn(false);

        $transfers = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(4)->get()->pluck('id')->toArray();

        $this->be($this->user());
        $response = $this->get(route('transactions.bulk.edit', $transfers));
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Requests\BulkEditJournalRequest
     */
    public function testUpdate(): void
    {
        $tags       = ['a', 'b', 'c'];
        $collection = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->take(4)->get();
        $allIds     = $collection->pluck('id')->toArray();

        $data = [
            'category'  => 'Some new category',
            'budget_id' => 1,
            'tags'      => 'a,b,c',
            'journals'  => $allIds,
        ];

        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->times(4)->andReturn(new TransactionJournal);

        $repository->shouldReceive('updateCategory')->times(4)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), $data['category']]);

        $repository->shouldReceive('updateBudget')->times(4)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), $data['budget_id']]);

        $repository->shouldReceive('updateTags')->times(4)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), ['tags' => $tags]]);


        $route = route('transactions.bulk.update');
        $this->be($this->user());
        $response = $this->post($route, $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Requests\BulkEditJournalRequest
     */
    public function testUpdateNull(): void
    {
        $tags       = ['a', 'b', 'c'];
        $collection = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->take(4)->get();
        $allIds     = $collection->pluck('id')->toArray();

        $data = [
            'category'  => 'Some new category',
            'budget_id' => 1,
            'tags'      => 'a,b,c',
            'journals'  => $allIds,
        ];

        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->times(4)->andReturn(new TransactionJournal, null);

        $repository->shouldReceive('updateCategory')->times(1)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), $data['category']]);

        $repository->shouldReceive('updateBudget')->times(1)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), $data['budget_id']]);

        $repository->shouldReceive('updateTags')->times(1)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), ['tags' => $tags]]);


        $route = route('transactions.bulk.update');
        $this->be($this->user());
        $response = $this->post($route, $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
