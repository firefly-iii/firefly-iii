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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
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
use Preferences;
use Steam;
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testAdd(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();


        $piggyRepos->shouldReceive('getCurrentAmount')->andReturn('0');
        $piggyRepos->shouldReceive('leftOnAccount')->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add', [$piggyBank->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testAddMobile(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();


        $piggyRepos->shouldReceive('getCurrentAmount')->andReturn('0');
        $piggyRepos->shouldReceive('leftOnAccount')->andReturn('0');

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add-money-mobile', [$piggyBank->id]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testCreate(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_piggy-banks_create');
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $this->mock(PiggyBankRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // new account list thing.
        $currency = $this->getEuro();
        $currencyRepos->shouldReceive('findNull')->andReturn($currency);

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);
        Amount::shouldReceive('balance')->andReturn('0');


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
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $piggyBank = $this->getRandomPiggyBank();
        $this->mock(PiggyBankRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.delete', [$piggyBank->id]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $piggyBank  = $this->getRandomPiggyBank();
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('destroy')->andReturn(true);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['piggy-banks.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.destroy', [$piggyBank->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testEdit(): void
    {
        $this->mockDefaultSession();
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();
        $this->mock(PiggyBankRepositoryInterface::class);

        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('123');

        // mock stuff
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


        // mock stuff for new account list thing.
        $currency = $this->getEuro();
        $account  = $this->getRandomAsset();

        $currencyRepos->shouldReceive('findNull')->andReturn($currency);


        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(new Collection([$account]))->once();

        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);
        Amount::shouldReceive('balance')->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.edit', [$piggyBank->id]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_piggy-banks_index');
        // mock stuff
        $repository         = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);
        $transformer        = $this->mock(PiggyBankTransformer::class);
        $accountTransformer = $this->mock(AccountTransformer::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

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
        $this->mockDefaultSession();
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('canAddAmount')->once()->andReturn(true);
        $repository->shouldReceive('addAmount')->once()->andReturn(true);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [$piggyBank]), $data);
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
        $this->mockDefaultSession();
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');


        $repository->shouldReceive('canAddAmount')->once()->andReturn(false);

        $data = ['amount' => '1000'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [$piggyBank->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostRemove(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('canRemoveAmount')->once()->andReturn(true);
        $repository->shouldReceive('removeAmount')->once()->andReturn(true);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [$piggyBank->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testPostRemoveTooMuch(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $repository->shouldReceive('canRemoveAmount')->once()->andReturn(false);

        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [$piggyBank->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testRemove(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $piggyBank     = $this->getRandomPiggyBank();
        $repetition    = PiggyBankRepetition::first();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        $piggyRepos->shouldReceive('getRepetition')->once()->andReturn($repetition);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove', [$piggyBank->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testRemoveMobile(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $repetition    = PiggyBankRepetition::first();
        $piggyBank     = $this->getRandomPiggyBank();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($this->getEuro())->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $piggyRepos->shouldReceive('getRepetition')->once()->andReturn($repetition);

        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove-money-mobile', [$piggyBank->id]));
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
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $piggyBank  = $this->getRandomPiggyBank();
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $repository->shouldReceive('setOrder')->once()->withArgs([Mockery::any(), 3])->andReturn(false);

        $data = ['order' => '3'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.set-order', [$piggyBank->id]), $data);
        $response->assertStatus(200);
        $response->assertExactJson(['data' => 'OK']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController
     */
    public function testShow(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_piggy-banks_show');
        // mock stuff
        $first        = $this->user()->transactionJournals()->inRandomOrder()->first();
        $repository   = $this->mock(PiggyBankRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $transformer  = $this->mock(PiggyBankTransformer::class);
        $piggyBank    = $this->getRandomPiggyBank();
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5, 'current_amount' => '5', 'currency_symbol' => 'x', 'target_amount' => '5', 'left_to_save' => '5', 'save_per_month' => '5']
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->andReturn($first)->atLeast()->once();
        $repository->shouldReceive('getEvents')->andReturn(new Collection)->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');


        $this->be($this->user());
        $response = $this->get(route('piggy-banks.show', [$piggyBank->id]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\PiggyBankController
     * @covers       \FireflyIII\Http\Requests\PiggyBankFormRequest
     */
    public function testStore(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $repository->shouldReceive('store')->andReturn(new PiggyBank);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['piggy-banks.create.uri' => 'http://localhost']);
        $data = [
            'name'                            => 'Piggy ' . $this->randomInt(),
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
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $piggyBank  = $this->getRandomPiggyBank();
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $repository->shouldReceive('update')->andReturn(new PiggyBank);

        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['piggy-banks.edit.uri' => 'http://localhost']);
        $data = [
            'id'                              => 3,
            'name'                            => 'Updated Piggy ' . $this->randomInt(),
            'targetamount'                    => '100.123',
            'account_id'                      => 2,
            'amount_currency_id_targetamount' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.update', [$piggyBank->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }
}
