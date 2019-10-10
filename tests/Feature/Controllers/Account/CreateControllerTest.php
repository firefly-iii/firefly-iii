<?php
/**
 * CreateControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Account;


use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class CreateControllerTest.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Account\CreateController
     */
    public function testCreate(): void
    {
        // mock stuff

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn(new Collection);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        // mock default calls
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_accounts_create_asset');

        // get all types:
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Debt'])->andReturn(AccountType::find(11))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Loan'])->andReturn(AccountType::find(9))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Mortgage'])->andReturn(AccountType::find(12))->once();

        $this->be($this->user());
        $response = $this->get(route('accounts.create', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\CreateController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testStore(): void
    {
        // mock stuff

        $repository = $this->mock(AccountRepositoryInterface::class);
        $asset      = $this->getRandomAsset();

        $repository->shouldReceive('store')->once()->andReturn($asset);

        // mock default session stuff
        $this->mockDefaultSession();

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['frontPageAccounts', [$asset->id]]);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        $this->session(['accounts.create.uri' => 'http://localhost/x']);
        $this->be($this->user());
        $data = [
            'name'       => 'new account ' . $this->randomInt(),
            'objectType' => 'asset',
        ];

        $response = $this->post(route('accounts.store', ['asset']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect('http://localhost/x');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\CreateController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testStoreAnother(): void
    {
        // mock stuff
        $repository = $this->mock(AccountRepositoryInterface::class);
        $asset      = $this->getRandomAsset();

        $repository->shouldReceive('store')->once()->andReturn($asset);

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['frontPageAccounts', [$asset->id]]);


        // mock default session stuff
        $this->mockDefaultSession();


        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        $this->session(['accounts.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'           => 'new account ' . $this->randomInt(),
            'objectType'     => 'asset',
            'create_another' => 1,
        ];


        $response = $this->post(route('accounts.store', ['asset']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect('http://localhost/accounts/create/asset');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\CreateController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testStoreLiability(): void
    {
        // mock stuff
        $repository = $this->mock(AccountRepositoryInterface::class);
        $liability  = $this->getRandomLoan();
        $loan       = AccountType::where('type', AccountType::LOAN)->first();
        $repository->shouldReceive('store')->once()->andReturn($liability);

        // mock default session stuff
        $this->mockDefaultSession();

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        $this->session(['accounts.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'                 => 'new liability account ' . $this->randomInt(),
            'objectType'           => 'liabilities',
            'liability_type_id'    => $loan->id,
            'opening_balance'      => '-100',
            'opening_balance_date' => '2018-01-01',
        ];

        $response = $this->post(route('accounts.store', ['liabilities']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
