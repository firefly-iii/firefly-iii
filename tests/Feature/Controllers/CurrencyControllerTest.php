<?php
/**
 * CurrencyControllerTest.php
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

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class CurrencyControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrencyControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCannotCreate(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('currencies.create'));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCannotDelete(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('currencyInUse')->andReturn(true);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [2]));
        $response->assertStatus(302);
        // has bread crumb
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCannotDestroy(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $repository->shouldReceive('currencyInUse')->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCreate(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDefaultCurrency(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $currencyRepos->shouldReceive('enable')->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.default', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDelete(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('currencyInUse')->andReturn(false);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [2]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDestroy(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $repository->shouldReceive('currencyInUse')->andReturn(false);
        $repository->shouldReceive('destroy')->andReturn(true)->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(1)->andReturn(true);

        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisable(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $currency   = TransactionCurrency::first();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection([$currency]));

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$currency->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableInUse(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $currency   = TransactionCurrency::first();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(true);
        $repository->shouldNotReceive('disable');

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$currency->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableNothingLeft(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $currency   = TransactionCurrency::first();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection);
        $repository->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$currency->id]));
        $response->assertStatus(500);
        $response->assertSee('No currencies found.');
    }



    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableEnableFirst(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $currency   = TransactionCurrency::first();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection);
        $repository->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$currency]));
        $repository->shouldReceive('enable')->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$currency->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testEdit(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testEnable(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $currency   = TransactionCurrency::first();

        $repository->shouldReceive('enable')->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.enable', [$currency->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testIndex(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $currencies = TransactionCurrency::get();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCurrencyByPreference')->andReturn($currencies->first());
        $repository->shouldReceive('getAll')->andReturn($currencies);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testIndexNoRights(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCurrencyByPreference')->andReturn(new TransactionCurrency);
        $repository->shouldReceive('getAll')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Requests\CurrencyFormRequest
     */
    public function testStore(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(new TransactionCurrency);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.create.uri' => 'http://localhost']);
        $data = [
            'name'           => 'XX',
            'code'           => 'XXX',
            'symbol'         => 'x',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Requests\CurrencyFormRequest
     */
    public function testStoreError(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturnNull();
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.create.uri' => 'http://localhost']);
        $data = [
            'name'           => 'XX',
            'code'           => 'XXX',
            'symbol'         => 'x',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Could not store the new currency.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Requests\CurrencyFormRequest
     */
    public function testStoreNoRights(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(new TransactionCurrency);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.create.uri' => 'http://localhost']);
        $data = [
            'name'           => 'XX',
            'code'           => 'XXX',
            'symbol'         => 'x',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     * @covers \FireflyIII\Http\Requests\CurrencyFormRequest
     */
    public function testUpdate(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->andReturn(new TransactionCurrency);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.edit.uri' => 'http://localhost']);
        $data = [
            'id'             => 2,
            'name'           => 'XA',
            'code'           => 'XAX',
            'symbol'         => 'a',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.update', [2]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
