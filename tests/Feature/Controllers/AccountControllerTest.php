<?php
/**
 * AccountControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Steam;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class AccountControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::create
     */
    public function testCreate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn(new Collection);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('accounts.create', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::delete
     * @covers \FireflyIII\Http\Controllers\Controller::rememberPreviousUri
     */
    public function testDelete()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET]])->andReturn(new Collection);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);


        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.delete', [$account->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::destroy
     * @covers \FireflyIII\Http\Controllers\Controller::__construct
     * @covers \FireflyIII\Http\Controllers\Controller::getPreviousUri
     */
    public function testDestroy()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->withArgs([0])->once()->andReturn(new Account);
        $repository->shouldReceive('destroy')->andReturn(true);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.delete.uri' => 'http://localhost/accounts/show/1']);
        $account = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();

        $this->be($this->user());
        $response = $this->post(route('accounts.destroy', [$account->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn(new Collection);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->once()->andReturn(new TransactionCurrency());

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.edit', [$account->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::index
     * @covers       \FireflyIII\Http\Controllers\AccountController::__construct
     * @covers       \FireflyIII\Http\Controllers\AccountController::isInArray
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range)
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        Steam::shouldReceive('balancesById')->andReturn([$account->id => '100']);
        Steam::shouldReceive('getLastActivities')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.index', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @covers       \FireflyIII\Http\Controllers\AccountController::getPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff:
        $tasker       = $this->mock(AccountTaskerInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tasker->shouldReceive('amountOutInPeriod')->withAnyArgs()->andReturn('-1');
        $tasker->shouldReceive('amountInInPeriod')->withAnyArgs()->andReturn('1');

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date)->once();

        $transaction = factory(Transaction::class)->make();
        $collector   = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10));


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range)
    {
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();

        $collector->shouldReceive('setTypes')->andReturnSelf();

        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10));


        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('amountOutInPeriod')->withAnyArgs()->andReturn('-1');
        $tasker->shouldReceive('amountInInPeriod')->withAnyArgs()->andReturn('1');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1, 'all']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\AccountController::show
     * @covers                   \FireflyIII\Http\Controllers\AccountController::redirectToOriginalAccount
     * @expectedExceptionMessage Expected a transaction
     */
    public function testShowBrokenInitial()
    {
        // mock
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 6)->orderBy('id', 'ASC')->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.show', [$account->id]));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10));

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(new Carbon)->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1, '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDateEmpty(string $range)
    {
        // mock stuff
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(new Carbon);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1, '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @covers       \FireflyIII\Http\Controllers\AccountController::redirectToOriginalAccount
     */
    public function testShowInitial()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 6)->orderBy('id', 'DESC')->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.show', [$account->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::store
     * @covers \FireflyIII\Http\Controllers\Controller::getPreviousUri
     */
    public function testStore()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->andReturn(new Account)->once();
        $repository->shouldReceive('store')->once()->andReturn(factory(Account::class)->make());
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name' => 'new account ' . rand(1000, 9999),
            'what' => 'asset',
        ];

        $response = $this->post(route('accounts.store', ['asset']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::store
     * @covers \FireflyIII\Http\Controllers\Controller::getPreviousUri
     */
    public function testStoreAnother()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->andReturn(new Account)->once();
        $repository->shouldReceive('store')->once()->andReturn(factory(Account::class)->make());
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'           => 'new account ' . rand(1000, 9999),
            'what'           => 'asset',
            'create_another' => 1,
        ];

        $response = $this->post(route('accounts.store', ['asset']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::update
     * @covers \FireflyIII\Http\Controllers\Controller::getPreviousUri
     */
    public function testUpdate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->andReturn(new Account)->once();
        $repository->shouldReceive('update')->once();
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.edit.uri' => 'http://localhost/javascript/account']);
        $this->be($this->user());
        $data = [
            'name'   => 'updated account ' . rand(1000, 9999),
            'active' => 1,
            'what'   => 'asset',
        ];

        $response = $this->post(route('accounts.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::update
     * @covers \FireflyIII\Http\Controllers\Controller::getPreviousUri
     */
    public function testUpdateAgain()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->andReturn(new Account)->once();
        $repository->shouldReceive('update')->once();
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'           => 'updated account ' . rand(1000, 9999),
            'active'         => 1,
            'what'           => 'asset',
            'return_to_edit' => '1',
        ];

        $response = $this->post(route('accounts.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
