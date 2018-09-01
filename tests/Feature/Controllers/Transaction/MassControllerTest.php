<?php
/**
 * MassControllerTest.php
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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class MassControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testDelete(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();


        $withdrawals = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();
        $this->be($this->user());
        $response = $this->get(route('transactions.mass.delete', $withdrawals));
        $response->assertStatus(200);
        $response->assertSee('Delete a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testDestroy(): void
    {

        $deposits   = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->take(2)->get();
        $depositIds = $deposits->pluck('id')->toArray();

        // mock deletion:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->andReturnValues([$deposits[0], $deposits[1]])->times(2);
        $repository->shouldReceive('destroy')->times(2);

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
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testEdit(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $transfers      = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(2)->get();
        $transfersArray = $transfers->pluck('id')->toArray();
        $source         = $this->user()->accounts()->first();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        // mock data for edit page:
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$source]));
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Transfer');
        $journalRepos->shouldReceive('isJournalReconciled')->andReturn(false);
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn($transfers->first()->transactions()->first());


        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        // mock more stuff:
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);


        $this->be($this->user());
        $response = $this->get(route('transactions.mass.edit', $transfersArray));
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testEditMultiple(): void
    {
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);


        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

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
        $route      = route('transactions.mass.edit', implode(',', $allIds));
        $this->be($this->user());
        $response = $this->get($route);
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('marked as reconciled');
        $response->assertSee('multiple source accounts');
        $response->assertSee('multiple destination accounts');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testUpdate(): void
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)
                                     ->whereNull('deleted_at')
                                     ->first();

        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->once();
        $repository->shouldReceive('findNull')->once()->andReturn($deposit);
        $repository->shouldReceive('getTransactionType')->andReturn('Deposit');
        $repository->shouldReceive('getNoteText')->andReturn('Some note');

        $this->session(['transactions.mass-edit.uri' => 'http://localhost']);

        $data = [
            'journals'                                  => [$deposit->id],
            'description'                               => [$deposit->id => 'Updated salary thing'],
            'amount'                                    => [$deposit->id => 1600],
            'amount_currency_id_amount_' . $deposit->id => 1,
            'date'                                      => [$deposit->id => '2014-07-24'],
            'source_name'                               => [$deposit->id => 'Job'],
            'destination_id'                            => [$deposit->id => 1],
            'category'                                  => [$deposit->id => 'Salary'],
        ];

        $this->be($this->user());
        $response = $this->post(route('transactions.mass.update', [$deposit->id]), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }
}
