<?php
/**
 * ConvertControllerTest.php
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

use Amount;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ConvertControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConvertControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexDepositTransfer(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // find deposit:
        $deposit = $this->getRandomDeposit();
        $journalRepos->shouldReceive('firstNull')->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();
        $account  = factory(Account::class)->make();
        $this->mock(CurrencyRepositoryInterface::class);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(2);
        Amount::shouldReceive('formatAnything')->andReturn('0')->once();

        $this->be($this->user());

        $response = $this->get(route('transactions.convert.index', ['transfer', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexDepositWithdrawal(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // find deposit:
        $deposit = $this->getRandomDeposit();
        $journalRepos->shouldReceive('firstNull')->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->twice();
        Amount::shouldReceive('formatAnything')->andReturn('0')->once();


        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexSameType(): void
    {
        // mock stuff:

        // find deposit:
        $deposit      = $this->getRandomDeposit();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();

        $this->be($this->user());

        $response = $this->get(route('transactions.convert.index', ['deposit', $deposit->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexSplit(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();

        // mock stuff for new account list thing.
        $currency      = TransactionCurrency::first();
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $currencyRepos->shouldReceive('findNull')->andReturn($currency);

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->once();

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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexTransferDeposit(): void
    {
        // mock stuff:

        // find transfer:
        $transfer     = $this->getRandomTransfer();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->andReturn($transfer);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexTransferWithdrawal(): void
    {
        // find transfer:
        $transfer = $this->getRandomTransfer();
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();
        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(2);
        Amount::shouldReceive('formatAnything')->andReturn('0')->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexWithdrawalDeposit(): void
    {

        // find withdrawal:
        $withdrawal = $this->getRandomWithdrawal();
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();
        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(2);
        Amount::shouldReceive('formatAnything')->andReturn('0')->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexWithdrawalTransfer(): void
    {
        // find withdrawal:
        $withdrawal = $this->getRandomWithdrawal();
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();
        $this->mock(CurrencyRepositoryInterface::class);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(2);
        Amount::shouldReceive('formatAnything')->andReturn('0')->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['transfer', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexDepositTransfer(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // get journal:
        $deposit     = $this->getRandomDeposit();
        $source      = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();

        $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->andReturn(new Account)->atLeast()->once();


        $data = ['source_account_asset' => 1];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexDepositWithdrawal(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // get journal:
        $deposit     = $this->getRandomDeposit();
        $source      = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();
        $expense     = $this->getRandomExpense();

        $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('store')->atLeast()->once()->andReturn($expense);

        $data = ['destination_account_expense' => 'New expense name.'];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexDepositWithdrawalEmptyName(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // get journal:
        $deposit     = $this->getRandomDeposit();
        $source      = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();
        $expense     = $this->getRandomExpense();

        $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('getCashAccount')->atLeast()->once()->andReturn($expense);

        $data = ['destination_account_expense' => ''];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexErrored(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock stuff
        $messageBag = new MessageBag;
        $messageBag->add('fake', 'fake error');

        // get journal:
        $withdrawal  = $this->getRandomWithdrawal();
        $source      = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();

        $repository->shouldReceive('convert')->andReturn($messageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->andReturn(new Account)->atLeast()->once();

        $data = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.convert.index', ['transfer', $withdrawal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexSameType(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // get journal:
        $withdrawal = $this->getRandomWithdrawal();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();

        $data = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['withdrawal', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexSplit(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // get journal:
        $withdrawal = $this->getRandomSplitWithdrawal();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();

        $data = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexTransferDeposit(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock stuff

        // get journal:
        $transfer    = $this->getRandomTransfer();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomAsset();
        $revenue     = $this->getRandomRevenue();

        $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('store')->atLeast()->once()->andReturn($revenue);

        $data = ['source_account_revenue' => 'New rev'];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['deposit', $transfer->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$transfer->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexWithdrawalDeposit(): void
    {
        // mock stuff
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $withdrawal  = $this->getRandomWithdrawal();
        $source      = $this->getRandomExpense();
        $destination = $this->getRandomAsset();
        $revenue     = $this->getRandomRevenue();

        $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
        $accountRepos->shouldReceive('store')->atLeast()->once()->andReturn($revenue);


        $data = ['source_account_revenue' => 'New revenue name.'];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['deposit', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }

        /**
         * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
         */
        public function testPostIndexWithdrawalDepositEmptyName(): void
        {
            // mock stuff
            $repository = $this->mock(JournalRepositoryInterface::class);
            $userRepos  = $this->mock(UserRepositoryInterface::class);
            $accountRepos = $this->mock(AccountRepositoryInterface::class);

            $withdrawal  = $this->getRandomWithdrawal();
            $source      = $this->getRandomExpense();
            $destination = $this->getRandomAsset();
            $revenue     = $this->getRandomRevenue();

            $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
            $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
            $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
            $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
            $accountRepos->shouldReceive('getCashAccount')->atLeast()->once()->andReturn($revenue);

            $data       = ['source_account_revenue' => ''];
            $this->be($this->user());
            $response = $this->post(route('transactions.convert.index', ['deposit', $withdrawal->id]), $data);
            $response->assertStatus(302);
            $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
        }

        /**
         * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
         */
        public function testPostIndexWithdrawalTransfer(): void
        {
            // mock stuff
            $repository = $this->mock(JournalRepositoryInterface::class);
            $userRepos  = $this->mock(UserRepositoryInterface::class);
            $accountRepos = $this->mock(AccountRepositoryInterface::class);

            $withdrawal  = $this->getRandomWithdrawal();
            $source      = $this->getRandomExpense();
            $destination = $this->getRandomAsset();
            $newDest = $this->getRandomAsset();

            $repository->shouldReceive('convert')->andReturn(new MessageBag)->atLeast()->once();
            $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal)->atLeast()->once();
            $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$source]))->atLeast()->once();
            $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$destination]))->atLeast()->once();
            $accountRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($newDest);

            $data       = ['destination_account_asset' => 2,];
            $this->be($this->user());
            $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
            $response->assertStatus(302);
            $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
        }
}
