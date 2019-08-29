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

use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCannotCreate(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_currencies_create');

        // mock stuff
        $this->mock(CurrencyRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->once()->andReturn(false);

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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $repository->shouldReceive('currencyInUse')->andReturn(true);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [$euro->id]));
        $response->assertStatus(302);
        // has bread crumb
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCannotDestroy(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $repository->shouldReceive('currencyInUse')->andReturn(true);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);


        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [$euro->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testCreate(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_currencies_create');

        // mock stuff
        $this->mock(CurrencyRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

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
        $this->mockDefaultSession();

        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);
        $euro = $this->getEuro();
        $currencyRepos->shouldReceive('enable')->once();

        Preferences::shouldReceive('mark')->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', $euro->code])->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.default', [$euro->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDelete(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();


        $repository->shouldReceive('currencyInUse')->andReturn(false);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [$euro->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $repository->shouldReceive('currencyInUse')->andReturn(false);
        $repository->shouldReceive('destroy')->andReturn(true)->once();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(1)->andReturn(true);

        $this->session(['currencies.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [$euro->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisable(): void
    {
        $this->mockDefaultSession();

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        Preferences::shouldReceive('mark')->atLeast()->once();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection([$euro]));

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$euro->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableEnableFirst(): void
    {
        $this->mockDefaultSession();

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection);
        $repository->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$euro]));
        $repository->shouldReceive('enable')->atLeast()->once()->andReturn(true);
        Preferences::shouldReceive('mark')->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', $euro->code])->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$euro->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableInUse(): void
    {
        $this->mockDefaultSession();

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInUseAt')->atLeast()->once()->andReturn('accounts');

        $repository->shouldNotReceive('disable');
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$euro->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testDisableNothingLeft(): void
    {
        $this->mockDefaultSession();

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();

        $userRepos->shouldReceive('hasRole')->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('currencyInuse')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('disable')->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('get')->atLeast()->once()->andReturn(new Collection);
        $repository->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection);
        Preferences::shouldReceive('mark')->atLeast()->once();

        Log::warning('The following error is part of a test.');
        $this->be($this->user());
        $response = $this->get(route('currencies.disable', [$euro->id]));
        $response->assertStatus(500);
        $response->assertSee('No currencies found.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testEdit(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $this->mock(CurrencyRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $euro      = $this->getEuro();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('currencies.edit', [$euro->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testEnable(): void
    {
        $this->mockDefaultSession();

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);
        $euro = $this->getEuro();

        $repository->shouldReceive('enable')->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('currencies.enable', [$euro->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_currencies_index');

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        $currencies = TransactionCurrency::get();


        $repository->shouldReceive('getCurrencyByPreference')->andReturn($currencies->first());
        $repository->shouldReceive('getAll')->andReturn($currencies);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $pref       = new Preference;
        $pref->data = 'EUR';
        Preferences::shouldReceive('get')->withArgs(['currencyPreference', 'EUR'])->atLeast()->once()->andReturn($pref);

        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController
     */
    public function testIndexNoRights(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_currencies_index');

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);


        $repository->shouldReceive('getCurrencyByPreference')->andReturn(new TransactionCurrency);
        $repository->shouldReceive('getAll')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(false);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $pref       = new Preference;
        $pref->data = 'EUR';
        Preferences::shouldReceive('get')->withArgs(['currencyPreference', 'EUR'])->atLeast()->once()->andReturn($pref);

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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);


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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);


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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);


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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);


        $repository->shouldReceive('update')->andReturn(new TransactionCurrency);
        $userRepos->shouldReceive('hasRole')->once()->andReturn(true);
        $repository->shouldReceive('currencyInUse')->atLeast()->once()->andReturn(true);
        Preferences::shouldReceive('mark')->atLeast()->once();

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
