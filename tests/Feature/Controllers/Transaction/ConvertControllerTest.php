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
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
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
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexDepositTransfer(): void
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // find deposit:
        $loop = 0;
        do {
            $deposit = TransactionJournal::where('transaction_type_id', 2)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count   = $deposit->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

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

        // find deposit:
        $loop = 0;
        do {
            $deposit = TransactionJournal::where('transaction_type_id', 2)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count   = $deposit->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

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
        $loop = 0;
        do {
            $deposit = TransactionJournal::where('transaction_type_id', 2)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count   = $deposit->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
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
        $loop = 0;
        do {
            $transfer = TransactionJournal::where('transaction_type_id', 3)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count    = $transfer->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
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
        $loop = 0;
        do {
            $transfer = TransactionJournal::where('transaction_type_id', 3)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count    = $transfer->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
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
        $loop = 0;
        do {
            $withdrawal = TransactionJournal::where('transaction_type_id', 1)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count      = $withdrawal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
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
        $loop = 0;
        do {
            $withdrawal = TransactionJournal::where('transaction_type_id', 1)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count      = $withdrawal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
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
        // mock stuff

        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexDepositWithdrawal(): void
    {
        // mock stuff

        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('store')->andReturn(new Account);
        $account = $this->user()->accounts()->first();

        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data    = ['destination_account_expense' => 'New expense name.'];
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
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $deposit      = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data         = ['destination_account_expense' => ''];
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
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->first();

        // find withdrawal:
        $loop = 0;
        do {
            $withdrawal = TransactionJournal::where('transaction_type_id', 1)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count      = $withdrawal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);


        // mock stuff
        $messageBag = new MessageBag;
        $messageBag->add('fake', 'fake error');
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn($messageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->twice();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->twice();
        $accountRepos->shouldReceive('findNull')->andReturn($account)->once();
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
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexSplit(): void
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexTransferDeposit(): void
    {
        // find transfer:
        $loop = 0;
        do {
            $transfer = TransactionJournal::where('transaction_type_id', 3)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count    = $transfer->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('store')->andReturn(new Account)->once();

        $account = $this->user()->accounts()->first();
        $repository->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->twice();
        $repository->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->twice();

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
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = ['source_account_revenue' => 'New revenue name.'];
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
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
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
        $repository->shouldReceive('convert')->andReturn(new MessageBag);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn(new Account);

        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $data       = ['destination_account_asset' => 2,];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }
}
