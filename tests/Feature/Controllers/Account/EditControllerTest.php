<?php
/**
 * EditControllerTest.php
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


use FireflyIII\Models\AccountType;
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
 * Class EditControllerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     */
    public function testEdit(): void
    {
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $asset        = $this->getRandomAsset();
        $euro         = $this->getEuro();

        // mock default session stuff
        $this->mockDefaultSession();


        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $repository->shouldReceive('get')->andReturn(new Collection)->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull()->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull()->atLeast()->once();
        //$accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('123')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_type'])->andReturn('')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_monthly_payment_date'])->andReturn('')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly')->atLeast()->once();

        // get all types:
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Debt'])->andReturn(AccountType::find(11))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Loan'])->andReturn(AccountType::find(9))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Mortgage'])->andReturn(AccountType::find(12))->once();

        $this->be($this->user());
        $response = $this->get(route('accounts.edit', [$asset->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('Some text');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     */
    public function testEditLiability(): void
    {
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $euro         = $this->getEuro();
        $loan         = $this->getRandomLoan();
        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        //$repository->shouldReceive('findNull')->once()->andReturn($euro);
        $repository->shouldReceive('get')->andReturn(new Collection);

        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull();
        //$accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_type'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_monthly_payment_date'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->once();

        // get all types:
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Debt'])->andReturn(AccountType::find(11))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Loan'])->andReturn(AccountType::find(9))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Mortgage'])->andReturn(AccountType::find(12))->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('accounts.edit', [$loan->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('Some text');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     */
    public function testEditNull(): void
    {
        // mock stuff
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $euro         = $this->getEuro();
        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $repository->shouldReceive('get')->andReturn(new Collection);
        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_type'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_monthly_payment_date'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->once();

        // mock default session stuff
        $this->mockDefaultSession();

        // get all types:
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Debt'])->andReturn(AccountType::find(11))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Loan'])->andReturn(AccountType::find(9))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Mortgage'])->andReturn(AccountType::find(12))->once();

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.edit', [$account->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('Some text');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testUpdate(): void
    {
        // mock stuff
        $account    = $this->getRandomAsset();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('update')->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $this->session(['accounts.edit.uri' => 'http://localhost/javascript/account']);
        $this->be($this->user());
        $data = [
            'name'   => 'updated account ' . $this->randomInt(),
            'active' => 1,
            'what'   => 'asset',
        ];

        $response = $this->post(route('accounts.update', [$account->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     * @covers \FireflyIII\Http\Requests\AccountFormRequest
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testUpdateAgain(): void
    {
        // mock stuff
        $account    = $this->getRandomAsset();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('update')->once();

        $this->session(['accounts.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'           => 'updated account ' . $this->randomInt(),
            'active'         => 1,
            'what'           => 'asset',
            'return_to_edit' => '1',
        ];

        Preferences::shouldReceive('mark')->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $response = $this->post(route('accounts.update', [$account->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
