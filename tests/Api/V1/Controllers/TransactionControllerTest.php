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


use Exception;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class TransactionControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Submit empty description.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailDescription(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'transactions' => [
                [
                    'description' => '',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.description' => ['The description field is required.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Fail the valid destination information test.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailDestination(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        /** fail destination */
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(false)
                  ->andSet('destError', 'Some error');

        $data = [
            'transactions' => [
                [
                    'description' => 'Fails anyway',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.destination_id'   => ['Some error'],
                    'transactions.0.destination_name' => ['Some error'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Submit foreign currency info, but no foreign currency amount.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailForeignCurrencyAmount(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'transactions' => [
                [
                    'description'           => 'Test',
                    'date'                  => '2018-01-01',
                    'type'                  => 'withdrawal',
                    'amount'                => '10',
                    'foreign_currency_code' => 'USD',
                    'source_id'             => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.foreign_amount' => ['The content of this field is invalid without foreign amount information.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Submit foreign currency, but no foreign currency info.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailForeignCurrencyInfo(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();


        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'transactions' => [
                [
                    'description'    => 'Test',
                    'date'           => '2018-01-01',
                    'type'           => 'withdrawal',
                    'amount'         => '10',
                    'foreign_amount' => '11',
                    'source_id'      => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.foreign_amount' => ['The content of this field is invalid without currency information.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Fail the valid source information test.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailSource(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        /** source info returns FALSE **/
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(false)
                  ->andSet('sourceError', 'Some error');

        $data = [
            'transactions' => [
                [
                    'description' => 'Some withdrawal ',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.source_id'   => ['Some error'],
                    'transactions.0.source_name' => ['Some error'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Submit multiple transactions but no group title.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailStoreGroupTitle(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'transactions' => [
                [
                    'description' => 'Some description',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
                [
                    'description' => 'Some description',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'group_title' => ['A group title is mandatory when there is more than one transaction.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Submit multiple transactions but no group title.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailStoreNoTransactions(): void
    {
        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();
        $data = [
            'transactions' => [
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.description' => ['Need at least one transaction.', 'The description field is required.'],
                    'transactions.0.type'        => ['Invalid transaction type.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Try to submit different transaction types for a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailTypes(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->withArgs(['deposit'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'group_title'  => 'Hi there',
            'transactions' => [
                [
                    'description' => 'Some description',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
                [
                    'description'    => 'Some description',
                    'date'           => '2018-01-01',
                    'type'           => 'deposit',
                    'amount'         => '10',
                    'destination_id' => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.source_id' => ['All accounts in this field must be equal.'],
                    'transactions.0.type'      => ['All splits must be of the same type.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Try to submit different transaction types for a deposit.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailTypesDeposit(): void
    {
        // mock data:
        $source = $this->getRandomAsset();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->withArgs(['deposit'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'group_title'  => 'Hi there',
            'transactions' => [
                [
                    'description'    => 'Some description',
                    'date'           => '2018-01-01',
                    'type'           => 'deposit',
                    'amount'         => '10',
                    'destination_id' => $source->id,
                ],
                [
                    'description' => 'Some description',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],

            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.destination_id' => ['All accounts in this field must be equal.'],
                    'transactions.0.type'           => ['All splits must be of the same type.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Try to submit different transaction types for a transfer.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreFailTypesTransfer(): void
    {
        // mock data:
        $source = $this->getRandomAsset();
        $dest   = $this->getRandomAsset($source->id);

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['transfer'])->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->withArgs(['deposit'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'group_title'  => 'Hi there',
            'transactions' => [
                [
                    'description'    => 'Some description',
                    'date'           => '2018-01-01',
                    'type'           => 'transfer',
                    'amount'         => '10',
                    'destination_id' => $source->id,
                    'source_id'      => $dest->id,
                ],
                [
                    'description'    => 'Some description',
                    'date'           => '2018-01-01',
                    'type'           => 'deposit',
                    'amount'         => '10',
                    'destination_id' => $dest->id,
                    'source_id'      => $source->id,
                ],

            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.destination_id' => ['All accounts in this field must be equal.'],
                    'transactions.0.source_id'      => ['All accounts in this field must be equal.'],
                    'transactions.0.type'           => ['All splits must be of the same type.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
    }

    /**
     * Submit the minimum amount of data required to create a single, unsplit withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionStoreRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testStoreOK(): void
    {
        // mock data:
        $source = $this->getRandomAsset();
        $group  = $this->getRandomWithdrawalGroup();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);
        $validator    = $this->mock(AccountValidator::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // transformer is called:
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 1]);
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once()->andReturn([]);

        // collector is called:
        $collector->shouldReceive('setTransactionGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAPIInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getGroups')->atLeast()->once()->andReturn(new Collection([[]]));

        // expect to store the group:
        $repository->shouldReceive('store')->atLeast()->once()->andReturn($group);

        // expect the event:
        try {
            $this->expectsEvents(StoredTransactionGroup::class);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }


        $data = [
            'transactions' => [
                [
                    'description' => 'Some description',
                    'date'        => '2018-01-01',
                    'type'        => 'withdrawal',
                    'amount'      => '10',
                    'source_id'   => $source->id,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.transactions.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertExactJson(
            [
                'data' => [
                    'attributes' => [],
                    'id'         => '1',
                    'links'      => [
                        'self' => 'http://localhost/api/v1/transactions/1',
                    ],
                    'type'       => 'transactions',
                ],
            ]
        );
    }

    /**
     * Submit a bad journal ID during update.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionUpdateRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testUpdateFailBadJournal(): void
    {
        // mock data:
        $group = $this->getRandomWithdrawalGroup();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        $validator->shouldReceive('setTransactionType')->withArgs(['invalid'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->withArgs([null, null])->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->withArgs([null, null])->atLeast()->once()->andReturn(true);

        $data = [
            'group_title'  => 'Empty',
            'transactions' => [
                [
                    'order'                  => 0,
                    'transaction_journal_id' => -1,
                    'reconciled'             => 'false',
                    'tags'                   => [],
                    'interest_date'          => '2019-01-01',
                    'description'            => 'Some new description',
                ],
                [
                    'order'                  => 0,
                    'transaction_journal_id' => -1,
                    'reconciled'             => 'false',
                    'tags'                   => [],
                    'interest_date'          => '2019-01-01',
                    'description'            => 'Some new description',
                ],
            ],
        ];

        // test API
        $response = $this->put(sprintf('/api/v1/transactions/%d', $group->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.source_name' => ['Each split must have transaction_journal_id (either valid ID or 0).'],
                    'transactions.1.source_name' => ['Each split must have transaction_journal_id (either valid ID or 0).'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );

    }

    /**
     * Update transaction but fail to submit equal transaction types.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionUpdateRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testUpdateFailTypes(): void
    {
        // mock data:
        $group = $this->getRandomWithdrawalGroup();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->withArgs(['deposit'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->withArgs([null, null])->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->withArgs([null, null])->atLeast()->once()->andReturn(true);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        $data = [
            'group_title'  => 'Empty',
            'transactions' => [
                [
                    'transaction_journal_id' => 0,
                    'order'                  => 0,
                    'reconciled'             => 'false',
                    'tags'                   => [],
                    'interest_date'          => '2019-01-01',
                    'type'                   => 'withdrawal',
                    'description'            => 'Some new description',
                ],
                [
                    'transaction_journal_id' => 0,
                    'order'                  => 0,
                    'reconciled'             => 'false',
                    'tags'                   => [],
                    'interest_date'          => '2019-01-01',
                    'type'                   => 'deposit',
                    'description'            => 'Some new description',
                ],
            ],
        ];

        // test API
        $response = $this->put(sprintf('/api/v1/transactions/%d', $group->id), $data, ['Accept' => 'application/json']);

        $response->assertExactJson(
            [
                'errors'  => [
                    'transactions.0.type' => ['All splits must be of the same type.'],
                ],
                'message' => 'The given data was invalid.',
            ]
        );
        $response->assertStatus(422);
    }

    /**
     * Submit the minimum amount of data to update a single withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Requests\TransactionUpdateRequest
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController
     */
    public function testUpdateOK(): void
    {
        // mock data:
        $group = $this->getRandomWithdrawalGroup();

        // mock repository
        $repository   = $this->mock(TransactionGroupRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);
        $validator    = $this->mock(AccountValidator::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $apiRepos     = $this->mock(JournalAPIRepositoryInterface::class);

        $validator->shouldReceive('setTransactionType')->withArgs(['invalid'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->withArgs([null, null])->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->withArgs([null, null])->atLeast()->once()->andReturn(true);

        // some mock calls:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $apiRepos->shouldReceive('setUser')->atLeast()->once();

        // call stuff:
        $repository->shouldReceive('update')->atLeast()->once()->andReturn($group);


        // transformer is called:
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 1]);
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once()->andReturn([]);

        // collector is called:
        $collector->shouldReceive('setTransactionGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAPIInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getGroups')->atLeast()->once()->andReturn(new Collection([[]]));

        $data = [
            'group_title'  => 'Empty',
            'transactions' => [
                [
                    'order'         => 0,
                    'reconciled'    => 'false',
                    'tags'          => [],
                    'interest_date' => '2019-01-01',
                    'description'   => 'Some new description',
                ],
            ],
        ];

        // expect the event:
        try {
            $this->expectsEvents(UpdatedTransactionGroup::class);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }

        // test API
        $response = $this->put(sprintf('/api/v1/transactions/%d', $group->id), $data, ['Accept' => 'application/json']);

        $response->assertExactJson(
            [
                'data' => [
                    'attributes' => [],
                    'id'         => '1',
                    'links'      => [
                        'self' => 'http://localhost/api/v1/transactions/1',
                    ],
                    'type'       => 'transactions',
                ],
            ]
        );
        $response->assertStatus(200);
    }

}
