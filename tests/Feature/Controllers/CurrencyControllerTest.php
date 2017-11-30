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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
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
     * @covers \FireflyIII\Http\Controllers\CurrencyController::create
     */
    public function testCannotCreate()
    {
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('currencies.create'));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::delete
     */
    public function testCannotDelete()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canDeleteCurrency')->andReturn(false);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [2]));
        $response->assertStatus(302);
        // has bread crumb
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::destroy
     */
    public function testCannotDestroy()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $repository->shouldReceive('canDeleteCurrency')->andReturn(false);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::create
     */
    public function testCreate()
    {
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::defaultCurrency
     */
    public function testDefaultCurrency()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('currencies.default', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('canDeleteCurrency')->andReturn(true);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [2]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $repository->shouldReceive('canDeleteCurrency')->andReturn(true);
        $repository->shouldReceive('destroy')->andReturn(true)->once();
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.edit', [2]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::index
     * @covers \FireflyIII\Http\Controllers\CurrencyController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCurrencyByPreference')->andReturn(new TransactionCurrency);
        $repository->shouldReceive('get')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::index
     * @covers \FireflyIII\Http\Controllers\CurrencyController::__construct
     */
    public function testIndexNoRights()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCurrencyByPreference')->andReturn(new TransactionCurrency);
        $repository->shouldReceive('get')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::store
     */
    public function testStore()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
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
     * @covers \FireflyIII\Http\Controllers\CurrencyController::store
     */
    public function testStoreNoRights()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
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
     * @covers \FireflyIII\Http\Controllers\CurrencyController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->andReturn(new TransactionCurrency);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->session(['currencies.edit.uri' => 'http://localhost']);
        $data = [
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
