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

use DB;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
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
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController::__construct
     */
    public function testEdit()
    {
        // mock stuff:
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);

        $transfers = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(4)->get()->pluck('id')->toArray();

        $this->be($this->user());
        $response = $this->get(route('transactions.bulk.edit', $transfers));
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController::edit
     */
    public function testEditMultiple()
    {
        // mock stuff:
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);

        // default transactions
        $collection = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(2)->get();

        // add deposit (with multiple sources)
        $collection->push(
            TransactionJournal::where('transaction_type_id', 2)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add withdrawal (with multiple destinations)
        $collection->push(
            TransactionJournal::where('transaction_type_id', 1)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add reconcile transaction
        $collection->push(
            TransactionJournal::where('transaction_type_id', 5)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add opening balance:
        $collection->push(TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first());
        $allIds = $collection->pluck('id')->toArray();
        $route  = route('transactions.bulk.edit', join(',', $allIds));
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
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController::edit
     */
    public function testEditMultipleNothingLeft()
    {
        // mock stuff:
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);

        // default transactions
        $collection = new Collection;

        // add deposit (with multiple sources)
        $collection->push(
            TransactionJournal::where('transaction_type_id', 2)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add withdrawal (with multiple destinations)
        $collection->push(
            TransactionJournal::where('transaction_type_id', 1)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add reconcile transaction
        $collection->push(
            TransactionJournal::where('transaction_type_id', 5)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );

        // add opening balance:
        $collection->push(TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first());
        $allIds = $collection->pluck('id')->toArray();
        $route  = route('transactions.bulk.edit', join(',', $allIds));
        $this->be($this->user());
        $response = $this->get($route);
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        $response->assertSessionHas('info');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSessionHas('error', 'You have selected no valid transactions to edit.');
        $response->assertSee('marked as reconciled');
        $response->assertSee('multiple source accounts');
        $response->assertSee('multiple destination accounts');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController::update
     */
    public function testUpdate()
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
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->times(4)->andReturn(new TransactionJournal);

        $repository->shouldReceive('updateCategory')->times(4)->andReturn(new TransactionJournal())
            ->withArgs([Mockery::any(), $data['category']]);

        $repository->shouldReceive('updateBudget')->times(4)->andReturn(new TransactionJournal())
                   ->withArgs([Mockery::any(), $data['budget_id']]);

        $repository->shouldReceive('updateTags')->times(4)->andReturn(true)
                   ->withArgs([Mockery::any(), $tags]);



        $route = route('transactions.bulk.update');
        $this->be($this->user());
        $response = $this->post($route, $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
