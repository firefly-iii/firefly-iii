<?php
/**
 * ConvertControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Transaction;


use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Tests\TestCase;

/**
 * Class ConvertControllerTest
 *
 * @package Tests\Feature\Controllers\Transaction
 */
class ConvertControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::__construct
     */
    public function testIndexDepositTransfer()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $this->be($this->user());
        $deposit  = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $response = $this->get(route('transactions.convert.index', ['transfer', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexDepositWithdrawal()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexSameType()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $this->be($this->user());
        $deposit  = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $response = $this->get(route('transactions.convert.index', ['deposit', $deposit->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexSplit()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                        ->groupBy('transaction_journals.id')
                                        ->orderBy('ct', 'DESC')
                                        ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);
        $response   = $this->get(route('transactions.convert.index', ['deposit', $withdrawal->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexTransferDeposit()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $transfer = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexTransferWithdrawal()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $transfer = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexWithdrawalDeposit()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexWithdrawalTransfer()
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->once()->andReturn(new Collection);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['transfer', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexDepositTransfer()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('find')->andReturn(new Account);

        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data    = ['source_account_asset' => 1];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexDepositWithdrawal()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('store')->andReturn(new Account);

        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data    = ['destination_account_expense' => 'New expense name.',];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexDepositWithdrawalEmptyName()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getCashAccount')->andReturn(new Account)->once();

        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data    = ['destination_account_expense' => '',];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexErrored()
    {
        // mock stuff
        $messageBag = new MessageBag;
        $messageBag->add('fake', 'fake error');
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn($messageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);


        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.convert.index', ['transfer', $withdrawal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexSameType()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexSplit()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                        ->groupBy('transaction_journals.id')
                                        ->orderBy('ct', 'DESC')
                                        ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);
        $data       = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexTransferDeposit()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('store')->andReturn(new Account)->once();

        $transfer = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->first();
        $data     = ['source_account_revenue' => 'New rev'];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['deposit', $transfer->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$transfer->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexWithdrawalDeposit()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('store')->andReturn(new Account)->once();

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = ['source_account_revenue' => 'New revenue name.',];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['deposit', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexWithdrawalDepositEmptyName()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getCashAccount')->andReturn(new Account)->once();

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = ['source_account_revenue' => '',];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['deposit', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getSourceAccount
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::getDestinationAccount
     */
    public function testPostIndexWithdrawalTransfer()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('find')->andReturn(new Account);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }


}
