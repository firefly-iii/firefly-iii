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


use Amount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class EditControllerTest
 */
class EditControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     */
    public function testEdit(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $repository->shouldReceive('get')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccType'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccMonthlyPaymentDate'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');

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
     */
    public function testEditLiability(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));
        $repository->shouldReceive('get')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccType'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccMonthlyPaymentDate'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');

        // get all types:
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Debt'])->andReturn(AccountType::find(11))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Loan'])->andReturn(AccountType::find(9))->once();
        $accountRepos->shouldReceive('getAccountTypeByType')->withArgs(['Mortgage'])->andReturn(AccountType::find(12))->once();


        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 12)->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.edit', [$account->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('Some text');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\EditController
     */
    public function testEditNull(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        Amount::shouldReceive('getDefaultCurrency')->andReturn(TransactionCurrency::find(2));
        $repository->shouldReceive('findNull')->once()->andReturn(null);
        $repository->shouldReceive('get')->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getNoteText')->andReturn('Some text')->once();
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturnNull();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturnNull();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccType'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccMonthlyPaymentDate'])->andReturn('');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');

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
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('update')->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.edit.uri' => 'http://localhost/javascript/account']);
        $this->be($this->user());
        $data = [
            'name'   => 'updated account ' . random_int(1000, 9999),
            'active' => 1,
            'what'   => 'asset',
        ];

        $response = $this->post(route('accounts.update', [1]), $data);
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
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('update')->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'           => 'updated account ' . random_int(1000, 9999),
            'active'         => 1,
            'what'           => 'asset',
            'return_to_edit' => '1',
        ];

        $response = $this->post(route('accounts.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
