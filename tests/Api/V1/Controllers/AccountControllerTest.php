<?php
/**
 * AccountControllerTest.php
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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);
        $currencyRepos->shouldReceive('setUser')->once();

        // get account:
        $account = $this->user()->accounts()->first();

        // call API
        $response = $this->delete('/api/v1/accounts/' . $account->id);
        $response->assertStatus(204);

    }

    /**
     * Test the list of accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testIndex(): void
    {
        // create stuff
        $accounts = factory(Account::class, 10)->create();

        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getAccountsByType')->withAnyArgs()->andReturn($accounts)->once();
        $currencyRepos->shouldReceive('setUser');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        // test API
        $response = $this->get('/api/v1/accounts');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertSee('type=all'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Opening balance without date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testInvalidBalance(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
            'opening_balance'   => '123.45',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'opening_balance_date' => ['The opening balance date field is required when opening balance is present.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * CC type present when account is a credit card.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testNoCreditCardData(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'ccAsset',
            'currency_id'       => 1,
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'credit_card_type'     => ['The credit card type field is required when account role is ccAsset.'],
                    'monthly_payment_date' => ['The monthly payment date field is required when account role is ccAsset.'],

                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * No currency information
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testNoCurrencyInfo(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'currency_code' => ['The currency code field is required when currency id is not present.'],
                    'currency_id'   => ['The currency id field is required when currency code is not present.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test the list of piggy banks.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testPiggyBanks(): void
    {
        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);

        // get piggies for this user.
        $piggies = $this->user()->piggyBanks()->get();
        $asset   = $this->getRandomAsset();

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser');
        $piggyRepos->shouldReceive('setUser');

        $repository->shouldReceive('getPiggyBanks')->andReturn($piggies)->once();
        $piggyRepos->shouldReceive('getCurrentAmount')->andReturn('12.45');
        $piggyRepos->shouldReceive('getSuggestedMonthlyAmount')->andReturn('12.45');
        $repository->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('');

        // test API
        $response = $this->get(route('api.v1.accounts.piggy_banks', [$asset->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            ['meta' => ['pagination' => ['total'       => $piggies->count(), 'count' => $piggies->count(), 'per_page' => true, 'current_page' => 1,
                                         'total_pages' => 1]],]
        );
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertSee('page=1'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show an account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */

    public function testShow(): void
    {
        // create stuff
        $account = $this->user()->accounts()->first();

        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10')->once();
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01')->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();


        // test API
        $response = $this->get('/api/v1/accounts/' . $account->id);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertSee('2018-01-01'); // opening balance date
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreLiability(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();
        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        // data to submit
        $data = [
            'name'                 => 'Some new liability account #' . random_int(1, 10000),
            'currency_id'          => 1,
            'type'                 => 'liability',
            'active'               => 1,
            'include_net_worth'    => 1,
            'liability_amount'     => '10000',
            'liability_start_date' => '2016-01-01',
            'liability_type'       => 'mortgage',
            'interest'             => '1',
            'interest_period'      => 'daily',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertSee($account->name);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Name already in use.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreNotUnique(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        $account = $this->user()->accounts()->where('account_type_id', 3)->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'name' => ['This account name is already in use.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreValid(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();
        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreWithCurrencyCode(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('findByCodeNull')->andReturn(TransactionCurrency::find(1));

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        // functions to expect:

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_code'     => 'EUR',
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsBasic(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());

        $asset = $this->getRandomAsset();

        // get some transactions using the collector:
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');

        $accountRepos->shouldReceive('isAsset')->atLeast()->once()->andReturnTrue();
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();


        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$asset->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsOpposing(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());

        $revenue            = $this->getRandomRevenue();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);

        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $accountRepos->shouldReceive('isAsset')->atLeast()->once()->andReturnFalse();
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$revenue->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsRange(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());

        $asset              = $this->getRandomAsset();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $accountRepos->shouldReceive('isAsset')->atLeast()->once()->andReturnTrue();
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();


        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$asset->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        $account = $this->user()->accounts()->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->put('/api/v1/accounts/' . $account->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testUpdateCurrencyCode(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('findByCodeNull')->andReturn(TransactionCurrency::find(1));

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('2')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('daily')->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn(true)->atLeast()->once();

        $account = $this->user()->accounts()->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_code'     => 'EUR',
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->put('/api/v1/accounts/' . $account->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }


}
