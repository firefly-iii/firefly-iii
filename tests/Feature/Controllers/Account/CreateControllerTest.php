<?php
/**
 * CreateControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Account;


use Amount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
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
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $euro         = $this->getEuro();
        $repository->shouldReceive('get')->andReturn(new Collection);

        // used for session range.
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);

        // mock default calls to Preferences:
        $this->mockDefaultPreferences();
        $this->mockIntroPreference('shown_demo_accounts_create_asset');

        // mock default calls to Configuration:
        $this->mockDefaultConfiguration();

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
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $asset        = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $repository->shouldReceive('store')->once()->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        // mock default calls to Configuration:
        $this->mockDefaultConfiguration();

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['frontPageAccounts', [$asset->id]]);
        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);


        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        // mock default calls to Preferences:
        $this->mockDefaultPreferences();



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
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $asset        = $this->getRandomAsset();
        $euro         = $this->getEuro();

        $repository->shouldReceive('store')->once()->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['frontPageAccounts', [$asset->id]]);
        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);


        // mock default calls to Preferences:
        $this->mockDefaultPreferences();
        //$this->mockIntroPreference('shown_demo_accounts_create_asset');

        // mock default calls to Configuration:
        $this->mockDefaultConfiguration();



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
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $liability    = $this->getRandomLoan();
        $loan         = AccountType::where('type', AccountType::LOAN)->first();
        $euro         = $this->getEuro();
        $repository->shouldReceive('store')->once()->andReturn($liability);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);

        // change the preference:
        $emptyPref       = new Preference;
        $emptyPref->data = [];
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs(['frontPageAccounts', []])->andReturn($emptyPref);

        // mock default calls to Preferences:
        $this->mockDefaultPreferences();
        //$this->mockIntroPreference('shown_demo_accounts_create_asset');

        // mock default calls to Configuration:
        $this->mockDefaultConfiguration();

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
