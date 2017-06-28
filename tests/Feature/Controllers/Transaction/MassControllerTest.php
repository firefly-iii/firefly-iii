<?php
/**
 * MassControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Transaction;


use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class MassControllerTest
 *
 * @package Tests\Feature\Controllers\Transaction
 */
class MassControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::delete
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::__construct
     */
    public function testDelete()
    {
        $withdrawals = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();
        $this->be($this->user());
        $response = $this->get(route('transactions.mass.delete', $withdrawals));
        $response->assertStatus(200);
        $response->assertSee('Delete a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::destroy
     */
    public function testDestroy()
    {
        $deposits   = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->take(2)->get();
        $depositIds = $deposits->pluck('id')->toArray();

        // mock deletion:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->andReturnValues([$deposits[0], $deposits[1]])->times(2);
        $repository->shouldReceive('delete')->times(2);

        $this->session(['transactions.mass-delete.uri' => 'http://localhost']);

        $data = [
            'confirm_mass_delete' => $depositIds,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.mass.destroy'), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::edit
     */
    public function testEdit()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        // mock more stuff:
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);

        $transfers = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();

        $this->be($this->user());
        $response = $this->get(route('transactions.mass.edit', $transfers));
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::edit
     */
    public function testEditMultiple()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

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

        // add opening balance:
        $collection->push(TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first());
        $allIds = $collection->pluck('id')->toArray();
        $route  = route('transactions.mass.edit', join(',', $allIds));
        $this->be($this->user());
        $response = $this->get($route);
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::edit
     */
    public function testEditMultipleNothingLeft()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        // default transactions
        $collection = new Collection;
        $collection->push(
            TransactionJournal::where('transaction_type_id', 1)
                              ->whereNull('transaction_journals.deleted_at')
                              ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->groupBy('transaction_journals.id')
                              ->orderBy('ct', 'DESC')
                              ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')])
        );
        $allIds = $collection->pluck('id')->toArray();

        $this->be($this->user());
        $response = $this->get(route('transactions.mass.edit', join(',', $allIds)));
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::update
     */
    public function testUpdate()
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)
                                     ->whereNull('deleted_at')
                                     ->first();
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->once();
        $repository->shouldReceive('find')->once()->andReturn($deposit);


        $this->session(['transactions.mass-edit.uri' => 'http://localhost']);

        $data = [
            'journals'                                  => [$deposit->id],
            'description'                               => [$deposit->id => 'Updated salary thing'],
            'amount'                                    => [$deposit->id => 1600],
            'amount_currency_id_amount_' . $deposit->id => 1,
            'date'                                      => [$deposit->id => '2014-07-24'],
            'source_account_name'                       => [$deposit->id => 'Job'],
            'destination_account_id'                    => [$deposit->id => 1],
            'category'                                  => [$deposit->id => 'Salary'],
        ];

        $this->be($this->user());
        $response = $this->post(route('transactions.mass.update', [$deposit->id]), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }
}
