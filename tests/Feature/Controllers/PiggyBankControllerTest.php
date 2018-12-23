<?php
/**
 * PiggyBankControllerTest.php
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

namespace Tests\Feature\Controllers;

use Amount;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class PiggyBankControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PiggyBankControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testAdd(): void
    {
        // mock stuff
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $piggyRepos->shouldReceive('getCurrentAmount')->andReturn('0');
        $piggyRepos->shouldReceive('leftOnAccount')->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testAddMobile(): void
    {
        // mock stuff
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $piggyRepos->shouldReceive('getCurrentAmount')->andReturn('0');
        $piggyRepos->shouldReceive('leftOnAccount')->andReturn('0');

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testCreate(): void
    {
        // mock stuff
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // new account list thing.
        $currency = TransactionCurrency::first();
        $account  = factory(Account::class)->make();
        $currencyRepos->shouldReceive('findNull')->andReturn($currency);

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);
        Amount::shouldReceive('balance')->andReturn('0');
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testDelete(): void
    {
        // mock stuff
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testDestroy(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['piggy-banks.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.destroy', [2]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testEdit(): void
    {
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);

        // mock stuff
        $account = factory(Account::class)->make();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        // mock stuff for new account list thing.
        $currency = TransactionCurrency::first();
        $account  = factory(Account::class)->make();

        $currencyRepos->shouldReceive('findNull')->andReturn($currency);


        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(new Collection([$account]))->once();

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);
        Amount::shouldReceive('balance')->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testIndex(): void
    {
        // mock stuff
        $currencyRepos      = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $repository         = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);
        $transformer        = $this->mock(PiggyBankTransformer::class);
        $accountTransformer = $this->mock(AccountTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5, 'current_amount' => '10', 'target_amount' => '10', 'currency_symbol' => 'x']
        );

        // mock transformer again
        $accountTransformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $accountTransformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5, 'current_balance' => '10', 'name' => 'Account', 'current_amount' => '5', 'currency_symbol' => 'x']
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


        $first   = $this->user()->transactionJournals()->inRandomOrder()->first();
        $piggies = $this->user()->piggyBanks()->take(2)->get();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn($first);
        $repository->shouldReceive('getPiggyBanks')->andReturn($piggies);
        $repository->shouldReceive('getCurrentAmount')->andReturn('10');
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('correctOrder');
        $repository->shouldReceive('getSuggestedMonthlyAmount')->andReturn('1');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostAdd(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
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
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostAddTooMuch(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canAddAmount')->once()->andReturn(false);

        $data = ['amount' => '1000'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostRemove(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
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
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostRemoveTooMuch(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canRemoveAmount')->once()->andReturn(false);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testRemove(): void
    {
        // mock stuff
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $repetition    = PiggyBankRepetition::first();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();
        $piggyRepos->shouldReceive('getRepetition')->once()->andReturn($repetition);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testRemoveMobile(): void
    {
        // mock stuff
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $repetition    = PiggyBankRepetition::first();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $piggyRepos->shouldReceive('getRepetition')->once()->andReturn($repetition);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * Test setting of order/
     *
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testSetOrder(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $repository->shouldReceive('setOrder')->once()->withArgs([Mockery::any(), 3])->andReturn(false);

        $data = ['order' => '3'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.set-order', [1]), $data);
        $response->assertStatus(200);
        $response->assertExactJson(['data' => 'OK']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testShow(): void
    {
        // mock stuff
        $first         = $this->user()->transactionJournals()->inRandomOrder()->first();
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(PiggyBankTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5,'current_amount' => '5','currency_symbol' => 'x','target_amount' => '5','left_to_save' => '5','save_per_month' => '5']);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->andReturn($first)->atLeast()->once();
        $repository->shouldReceive('getEvents')->andReturn(new Collection)->atLeast()->once();


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\PiggyBankController
     * @covers       \FireflyIII\Http\Requests\PiggyBankFormRequest
     */
    public function testStore(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(new PiggyBank);

        $this->session(['piggy-banks.create.uri' => 'http://localhost']);
        $data = [
            'name'                            => 'Piggy ' . random_int(999, 10000),
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
     * @covers       \FireflyIII\Http\Controllers\PiggyBankController
     * @covers       \FireflyIII\Http\Requests\PiggyBankFormRequest
     */
    public function testUpdate(): void
    {
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->andReturn(new PiggyBank);

        $this->session(['piggy-banks.edit.uri' => 'http://localhost']);
        $data = [
            'id'                              => 3,
            'name'                            => 'Updated Piggy ' . random_int(999, 10000),
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
