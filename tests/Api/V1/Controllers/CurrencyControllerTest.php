<?php
/**
 * CurrencyControllerTest.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use FireflyIII\Transformers\BillTransformer;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\CurrencyExchangeRateTransformer;
use FireflyIII\Transformers\CurrencyTransformer;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class CurrencyControllerTest
 */
class CurrencyControllerTest extends TestCase
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
     * Test the list of accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testAccounts(): void
    {
        // mock stuff:
        $currency      = TransactionCurrency::first();
        $account       = $this->getRandomAsset();
        $collection    = new Collection([$account]);
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(AccountTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAccountsByType')->withAnyArgs()->andReturn($collection)->atLeast()->once();
        $repository->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('1');

        // test API

        $response = $this->get(route('api.v1.currencies.accounts', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 1, 'count' => 1, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertSee('type=all'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show all available budgets.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testAvailableBudgets(): void
    {
        // mock stuff:
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(AvailableBudgetTransformer::class);
        $collection    = new Collection([AvailableBudget::first()]);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('getAvailableBudgets')->once()->andReturn($collection);

        // call API
        $currency = TransactionCurrency::first();
        $response = $this->get(route('api.v1.currencies.available_budgets', [$currency->code]));
        $response->assertStatus(200);
    }

    /**
     * Show all bills
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testBills(): void
    {
        // create stuff
        $paginator = new LengthAwarePaginator(new Collection([Bill::first()]), 0, 50, 1);
        // mock stuff:
        $repository    = $this->mock(BillRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(BillTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getPaginator')->withAnyArgs()->andReturn($paginator)->once();

        // test API
        $currency = TransactionCurrency::first();
        $response = $this->get(route('api.v1.currencies.bills', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testBudgetLimits(): void
    {
        $repository                           = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos                        = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos                            = $this->mock(UserRepositoryInterface::class);
        $transformer                          = $this->mock(BudgetLimitTransformer::class);
        $currency                             = TransactionCurrency::first();
        $budgetLimit                          = BudgetLimit::first();
        $budgetLimit->transaction_currency_id = $currency->id;
        $collection                           = new Collection([$budgetLimit]);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('getAllBudgetLimits')->once()->andReturn($collection);
        $currencyRepos->shouldReceive('setUser')->once();

        $response = $this->get(route('api.v1.currencies.budget_limits', [$currency->code]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testCer(): void
    {
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyExchangeRateTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls
        $repository->shouldReceive('setUser')->once()->atLeast()->once();
        $repository->shouldReceive('getExchangeRates')->once()->andReturn(new Collection);


        $currency = TransactionCurrency::first();
        $response = $this->get(route('api.v1.currencies.cer', [$currency->code]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Send delete
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        $userRepos->shouldReceive('hasRole')->once()->withArgs([Mockery::any(), 'owner'])->andReturn(true);
        $repository->shouldReceive('currencyInUse')->once()->andReturn(false);

        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get a currency
        $currency = TransactionCurrency::first();

        // call API
        $response = $this->delete('/api/v1/currencies/' . $currency->code);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testDisable(): void
    {
        // create stuff
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('disable')->once();
        $repository->shouldReceive('currencyInUse')->once()->andReturnFalse();

        // test API
        $response = $this->post(route('api.v1.currencies.disable', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'currencies',
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testDisableInUse(): void
    {
        // create stuff
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('currencyInUse')->once()->andReturnTrue();

        // test API
        $response = $this->post(route('api.v1.currencies.disable', [$currency->code]));
        $response->assertStatus(409);
        $response->assertJson([]);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testEnable(): void
    {
        // create stuff
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('enable')->once();

        // test API
        $response = $this->post(route('api.v1.currencies.enable', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'currencies',
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testIndex(): void
    {
        // mock stuff:
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAll')->withNoArgs()->andReturn(new Collection)->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // test API
        $response = $this->get('/api/v1/currencies');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            [
                'meta' => [
                    'pagination' => [
                        'total'        => 0,
                        'count'        => 0,
                        'per_page'     => true, // depends on user preference.
                        'current_page' => 1,
                        'total_pages'  => 1,
                    ],
                ],
            ]
        );
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testMakeDefault(): void
    {
        // create stuff
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('enable')->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->post(route('api.v1.currencies.default', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'currencies',
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testRecurrences(): void
    {
        // mock stuff:
        $recurrence     = Recurrence::first();
        $repository    = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(RecurrenceTransformer::class);

        // mock calls:
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getAll')->once()->andReturn(new Collection([$recurrence]));

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // call API
        $currency = TransactionCurrency::first();
        $response = $this->get(route('api.v1.currencies.recurrences', [$currency->code]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testRules(): void
    {
        $ruleRepos     = $this->mock(RuleRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $transformer   = $this->mock(RuleTransformer::class);


        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $ruleRepos->shouldReceive('getAll')->once()->andReturn(new Collection([Rule::first()]));
        $currencyRepos->shouldReceive('setUser')->once();

        // call API
        $currency = TransactionCurrency::first();
        $response = $this->get(route('api.v1.currencies.rules', [$currency->code]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Test show of a currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testShow(): void
    {
        // create stuff
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->get('/api/v1/currencies/' . $currency->code);
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'currencies',
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testStore(): void
    {

        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($currency);

        // data to submit:
        $data = [
            'name'           => 'New currency',
            'code'           => 'ABC',
            'symbol'         => 'A',
            'decimal_places' => 2,
            'default'        => '0',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->post('/api/v1/currencies', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new currency and make it default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testStoreWithDefault(): void
    {
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $preference       = new Preference;
        $preference->data = 'EUR';
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        Preferences::shouldReceive('lastActivity')->once();
        Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'New currency',
            'code'           => 'ABC',
            'symbol'         => 'A',
            'decimal_places' => 2,
            'default'        => '1',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->post('/api/v1/currencies', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testTransactionsBasic(): void
    {
        $currency     = TransactionCurrency::first();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $transformer  = $this->mock(TransactionTransformer::class);
        $accountRepos->shouldReceive('setUser');

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setCurrency')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // test API
        $response = $this->get(route('api.v1.currencies.transactions', [$currency->code]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testTransactionsRange(): void
    {
        $currency     = TransactionCurrency::first();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $transformer  = $this->mock(TransactionTransformer::class);
        $accountRepos->shouldReceive('setUser');

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setCurrency')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // test API
        $response = $this->get(
            route('api.v1.currencies.transactions', [$currency->code]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31'])
        );
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testUpdate(): void
    {
        $currency    = TransactionCurrency::first();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($currency);

        // data to submit:
        $data = [
            'name'           => 'Updated currency',
            'code'           => 'ABC',
            'symbol'         => '$E',
            'decimal_places' => '2',
            'default'        => '0',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->put('/api/v1/currencies/' . $currency->code, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update currency and make default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testUpdateWithDefault(): void
    {
        $currency         = TransactionCurrency::first();
        $repository       = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos        = $this->mock(UserRepositoryInterface::class);
        $transformer      = $this->mock(CurrencyTransformer::class);
        $preference       = new Preference;
        $preference->data = 'EUR';

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        Preferences::shouldReceive('lastActivity')->once();
        Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'Updated currency',
            'code'           => 'ABC',
            'symbol'         => '$E',
            'decimal_places' => '2',
            'default'        => '1',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->put('/api/v1/currencies/' . $currency->code, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }
}
