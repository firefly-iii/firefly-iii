<?php
/**
 * TransactionControllerTest.php
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


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * todo test bad budget, bad category
 * todo test bad piggy, bad bill
 * todo test fire of rules with parameter
 * todo test bad currency, bad foreign currency
 * todo test reconciled, identifier
 * Class TransactionControllerTest
 */
class TransactionControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
    }

    /**
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::delete
     */
    public function testDelete()
    {
        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('delete')->once()->andReturn(true);

        // get account:
        $transaction = $this->user()->transactions()->first();

        // call API
        $response = $this->delete('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(204);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::index
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::mapTypes
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testIndex()
    {
        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setLimit(5)->setPage(1);
        $paginator = $collector->getPaginatedJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get('/api/v1/transactions');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => true, 'count' => true, 'per_page' => 5, 'current_page' => 1, 'total_pages' => true]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::index
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::mapTypes
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testIndexWithRange()
    {
        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setLimit(5)->setPage(1);
        $paginator = $collector->getPaginatedJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get('/api/v1/transactions?start=2018-01-01&end=2018-01-31');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            ['meta' =>
                 ['pagination' =>
                      [
                          'total'        => true,
                          'count'        => true,
                          'per_page'     => 5,
                          'current_page' => 1,
                          'total_pages'  => true,
                      ],
                 ],
            ]
        );


        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::show
     */
    public function testShowDeposit()
    {
        /** @var TransactionJournal $journal */
        $journal     = auth()->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $transaction = $journal->transactions()->first();

        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        $collector->setLimit(5)->setPage(1);
        $transactions = $collector->getJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setJournals')->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        // test API
        $response = $this->get('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'attributes' => [
                        'description' => $journal->description,
                    ],
                    'links'      => [
                        0      => [],
                        'self' => true,
                    ],
                ],

            ]
        );

        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::show
     */
    public function testShowWithdrawal()
    {
        /** @var TransactionJournal $journal */
        $journal     = auth()->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $transaction = $journal->transactions()->first();

        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        $collector->setLimit(5)->setPage(1);
        $transactions = $collector->getJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setJournals')->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        // test API
        $response = $this->get('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'attributes' => [
                        'description' => $journal->description,
                    ],
                    'links'      => [
                        0      => [],
                        'self' => true,
                    ],
                ],

            ]
        );

        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Submit a transaction (withdrawal) with attached bill ID
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessBillId()
    {
        $bill    = auth()->user()->bills()->first();
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_id'      => $bill->id,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'bill_id'          => $bill->id,
                        'bill_name'        => $bill->name,
                        'amount'           => -10,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }


    /**
     * Submit a transaction (withdrawal) with attached bill ID
     * TODO also test deposit / transfer (should be ignored).
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessBillName()
    {
        $bill    = auth()->user()->bills()->first();
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_name'    => $bill->name,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'bill_id'          => $bill->id,
                        'bill_name'        => $bill->name,
                        'amount'           => -10,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreBasic()
    {
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit with existing budget ID, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreBudgetId()
    {
        $budget  = auth()->user()->budgets()->first();
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_id'   => $budget->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'budget_id'        => $budget->id,
                        'budget_name'      => $budget->name,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit with existing budget name, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreBudgetName()
    {
        $budget  = auth()->user()->budgets()->first();
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_name' => $budget->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'budget_id'        => $budget->id,
                        'budget_name'      => $budget->name,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit with existing category ID, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreCategoryID()
    {
        $category = auth()->user()->categories()->first();
        $account  = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data     = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'category_id' => $category->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'category_id'      => $category->id,
                        'category_name'    => $category->name,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit with existing category ID, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreCategoryName()
    {
        $category = auth()->user()->categories()->first();
        $account  = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data     = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'        => '10',
                    'currency_id'   => 1,
                    'source_id'     => $account->id,
                    'category_name' => $category->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'category_id'      => $category->id,
                        'category_name'    => $category->name,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Add foreign amount information.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreForeignAmount()
    {
        $currency = TransactionCurrency::first();
        $foreign  = TransactionCurrency::where('id', '!=', $currency->id)->first();
        $account  = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data     = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'              => '10',
                    'currency_id'         => $currency->id,
                    'foreign_currency_id' => $foreign->id,
                    'foreign_amount'      => 23,
                    'source_id'           => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'           => $data['description'],
                        'date'                  => $data['date'],
                        'source_id'             => $account->id,
                        'source_name'           => $account->name,
                        'type'                  => 'Withdrawal',
                        'currency_code'         => $currency->code,
                        'foreign_currency_code' => $foreign->code,
                        'foreign_amount'        => -23,
                        'source_type'           => 'Asset account',
                        'destination_name'      => 'Cash account',
                        'destination_type'      => 'Cash account',
                        'amount'                => -10,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Add all available meta data fields.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreMetaData()
    {
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'        => 'Some transaction #' . rand(1, 1000),
            'date'               => '2018-01-01',
            'type'               => 'withdrawal',
            // store date meta fields (if present):
            'interest_date'      => '2017-08-02',
            'book_date'          => '2017-08-03',
            'process_date'       => '2017-08-04',
            'due_date'           => '2017-08-05',
            'payment_date'       => '2017-08-06',
            'invoice_date'       => '2017-08-07',
            'internal_reference' => 'I are internal ref!',
            'transactions'       => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions?include=journal_meta', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertSee('interest_date');
        $response->assertSee('book_date');
        $response->assertSee('process_date');
        $response->assertSee('due_date');
        $response->assertSee('payment_date');
        $response->assertSee('invoice_date');
        $response->assertSee('internal_reference');
        $response->assertSee('2017-08-02');
        $response->assertSee('2017-08-03');
        $response->assertSee('2017-08-04');
        $response->assertSee('2017-08-05');
        $response->assertSee('2017-08-06');
        $response->assertSee('2017-08-07');
        $response->assertSee('I are internal ref!');
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreNotes()
    {
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'notes'        => 'I am a note',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertSee('I am a note');
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'notes'            => 'I am a note',
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * When sending a piggy bank by name, this must be reflected in the output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStorePiggyId()
    {
        $source = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $dest   = auth()->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $source->id)->first();
        $piggy  = auth()->user()->piggyBanks()->first();
        $data   = [
            'description'   => 'Some transfer #' . rand(1, 1000),
            'date'          => '2018-01-01',
            'type'          => 'transfer',
            'piggy_bank_id' => $piggy->id,
            'transactions'  => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $source->id,
                    'destination_id' => $dest->id,
                ],
            ],
        ];
        // test API
        $response = $this->post('/api/v1/transactions?include=piggy_bank_events', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data'     => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'type'             => 'Transfer',
                        'source_id'        => $source->id,
                        'source_name'      => $source->name,
                        'source_type'      => 'Asset account',
                        'destination_id'   => $dest->id,
                        'destination_name' => $dest->name,
                        'destination_type' => 'Asset account',
                        'amount'           => 10,
                    ],
                    'links'      => [],
                ],
                'included' => [
                    0 => [
                        'type'       => 'piggy_bank_events',
                        'attributes' => [
                            'amount' => 10,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * When sending a piggy bank by name, this must be reflected in the output.
     * TODO only when sending a transfer. Ignore it with withdrawals.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStorePiggyName()
    {
        $source = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $dest   = auth()->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $source->id)->first();
        $piggy  = auth()->user()->piggyBanks()->first();
        $data   = [
            'description'     => 'Some transfer #' . rand(1, 1000),
            'date'            => '2018-01-01',
            'type'            => 'transfer',
            'piggy_bank_name' => $piggy->name,
            'transactions'    => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $source->id,
                    'destination_id' => $dest->id,
                ],
            ],
        ];
        // test API
        $response = $this->post('/api/v1/transactions?include=piggy_bank_events', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data'     => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'type'             => 'Transfer',
                        'source_id'        => $source->id,
                        'source_name'      => $source->name,
                        'source_type'      => 'Asset account',
                        'destination_id'   => $dest->id,
                        'destination_name' => $dest->name,
                        'destination_type' => 'Asset account',
                        'amount'           => 10,
                    ],
                    'links'      => [],
                ],
                'included' => [
                    0 => [
                        'type'       => 'piggy_bank_events',
                        'attributes' => [
                            'amount' => 10,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Set a different reconciled var
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreReconciled()
    {
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'reconciled'  => true,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'type'       => 'transactions',
                    'attributes' => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                        'reconciled'       => true,
                    ],
                    'links'      => true,
                ],
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * Add some tags as well. Expect to see them in the result.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     */
    public function testSuccessStoreTags()
    {
        $tags    = [
            'TagOne' . rand(1, 1000),
            'TagTwoBlarg' . rand(1, 1000),
            'SomeThreeTag' . rand(1, 1000),
        ];
        $account = auth()->user()->accounts()->where('account_type_id', 3)->first();
        $data    = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'tags'         => join(',', $tags),
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions?include=tags', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        foreach ($tags as $tag) {
            $response->assertSee($tag);
        }
        $response->assertJson(
            [
                'data'     => [
                    'type'          => 'transactions',
                    'attributes'    => [
                        'description'      => $data['description'],
                        'date'             => $data['date'],
                        'source_id'        => $account->id,
                        'source_name'      => $account->name,
                        'type'             => 'Withdrawal',
                        'source_type'      => 'Asset account',
                        'destination_name' => 'Cash account',
                        'destination_type' => 'Cash account',
                        'amount'           => -10,
                    ],
                    'links'         => [],
                    'relationships' => [],
                ],
                'included' => [],
            ]
        );
    }

}