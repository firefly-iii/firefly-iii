<?php
/**
 * PiggyBankControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Steam;
use Tests\TestCase;

/**
 * Class PiggyBankControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class PiggyBankControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::add
     */
    public function testAdd()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::addMobile
     */
    public function testAddMobile()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::create
     */
    public function testCreate()
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection([$account]))->once();

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::create
     */
    public function testCreateEmpty()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection())->once();

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.create'));
        $response->assertStatus(302);
        $response->assertRedirect(route('new-user.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('destroy')->andReturn(true);


        $this->session(['piggy-banks.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.destroy', [2]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection([$account]))->once();

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::index
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $one = factory(PiggyBank::class)->make();
        $two = factory(PiggyBank::class)->make();
        $two->account_id = $one->account_id;
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getPiggyBanks')->andReturn(new Collection([$one, $two]));

        Steam::shouldReceive('balanceIgnoreVirtual')->twice()->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::order
     */
    public function testOrder()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('reset');
        $repository->shouldReceive('setOrder')->times(2);

        $this->be($this->user());
        $response = $this->post(route('piggy-banks.order'), ['order' => [1, 2]]);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAdd()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canAddAmount')->once()->andReturn(true);
        $repository->shouldReceive('addAmount')->once()->andReturn(true);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Add way too much
     *
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAddTooMuch()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canAddAmount')->once()->andReturn(false);


        $data = ['amount' => '1000'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postRemove
     */
    public function testPostRemove()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canRemoveAmount')->once()->andReturn(true);
        $repository->shouldReceive('removeAmount')->once()->andReturn(true);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postRemove
     */
    public function testPostRemoveTooMuch()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canRemoveAmount')->once()->andReturn(false);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::remove
     */
    public function testRemove()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::removeMobile
     */
    public function testRemoveMobile()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::show
     */
    public function testShow()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getEvents')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::store
     */
    public function testStore()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(new PiggyBank);

        $this->session(['piggy-banks.create.uri' => 'http://localhost']);
        $data = [
            'name'                            => 'Piggy ' . rand(999, 10000),
            'targetamount'                    => '100.123',
            'account_id'                      => 2,
            'amount_currency_id_targetamount' => 1,

        ];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->andReturn(new PiggyBank);

        $this->session(['piggy-banks.edit.uri' => 'http://localhost']);
        $data = [
            'name'                            => 'Updated Piggy ' . rand(999, 10000),
            'targetamount'                    => '100.123',
            'account_id'                      => 2,
            'amount_currency_id_targetamount' => 1,

        ];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.update', [3]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }


}
