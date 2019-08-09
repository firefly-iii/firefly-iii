<?php
/**
 * RecurrenceControllerTest.php
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

use Carbon\Carbon;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\Validation\AccountValidator;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class RecurrenceControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurrenceControllerTest extends TestCase
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
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreAssetId(): void
    {
        // get a recurrence:
        /** @var Recurrence $recurrence */
        $recurrence  = $this->user()->recurrences()->first();
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source name field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreAssetName(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([0, 'Checking Account'])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_name' => 'Checking Account',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Submit a deposit. Since most validators have been tested in other methods, dont bother too much.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreDeposit(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['deposit']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([null, 'Some expense account'])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([1, null])->andReturn(true);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'deposit',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'description'    => 'Test description deposit',
                    'source_name'    => 'Some expense account',
                    'destination_id' => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Add a recurring with correct reference to a destination (expense).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreDestinationId(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository     = $this->mock(RecurringRepositoryInterface::class);
        $transformer    = $this->mock(RecurrenceTransformer::class);
        $validator      = $this->mock(AccountValidator::class);
        $expenseAccount = $this->getRandomExpense();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([$expenseAccount->id, null])->andReturn(true);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'description'    => 'Test description',
                    'source_id'      => '1',
                    'destination_id' => $expenseAccount->id,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Add a recurring with correct reference to a destination (expense).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreDestinationName(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $expenseAccount = $this->getRandomExpense();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, $expenseAccount->name])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'           => '100',
                    'currency_id'      => '1',
                    'description'      => 'Test description',
                    'source_id'        => '1',
                    'destination_name' => $expenseAccount->name,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Includes both repetition count and an end date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailBothRepetitions(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $repeatUntil = new Carbon;
        $repeatUntil->addMonth();
        $data = [
            'type'              => 'withdrawal',
            'title'             => 'Hello',
            'first_date'        => $firstDate->format('Y-m-d'),
            'repeat_until'      => $repeatUntil->format('Y-m-d'),
            'nr_of_repetitions' => 10,
            'apply_rules'       => 1,
            'active'            => 1,
            'transactions'      => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'       => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repeat_until'      => [
                        'Require either a number of repetitions, or an end date (repeat_until). Not both.',
                    ],
                    'nr_of_repetitions' => [
                        'Require either a number of repetitions, or an end date (repeat_until). Not both.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit foreign amount but no currency information.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailForeignCurrency(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([null, 'Checking Account'])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'foreign_amount' => '100',
                    'description'    => 'Test description',
                    'source_name'    => 'Checking Account',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.foreign_amount' => [
                        'The content of this field is invalid without currency information.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidDaily(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '1',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Add a recurring but refer to an asset as destination.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidDestinationId(): void
    {
        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $validator    = $this->mock(AccountValidator::class);
        $assetAccount = $this->getRandomAsset();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([$assetAccount->id, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([$assetAccount->id, null])->andReturn(false);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'description'    => 'Test description',
                    'source_id'      => $assetAccount->id,
                    'destination_id' => $assetAccount->id,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.destination_id'   => [
                        null,
                    ],
                    'transactions.0.destination_name' => [
                        null,
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidMonthly(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'monthly',
                    'moment'  => '32',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidNdom(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'ndom',
                    'moment'  => '9,9',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidNdomCount(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'ndom',
                    'moment'  => '9',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidNdomHigh(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);


        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'ndom',
                    'moment'  => '4,9',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailInvalidWeekly(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'weekly',
                    'moment'  => '8',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'repetitions.0.moment' => [
                        'Invalid repetition moment for this type of repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit without a source account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailNoAsset(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([0, null])->andReturn(false);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '0',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id'   => [
                        null,
                        'This value is invalid for this field.',
                    ],
                    'transactions.0.source_name' => [
                        null,
                    ],
                ],
            ]
        );
        $response->assertStatus(422);

        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit with an expense account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailNotAsset(): void
    {
        // expense account:
        $expenseAccount = $this->getRandomExpense();

        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([$expenseAccount->id, null])->andReturn(false);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => $expenseAccount->id,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id'   => [
                        null,
                    ],
                    'transactions.0.source_name' => [
                        null,
                    ],
                ],
            ]
        );
        $response->assertStatus(422);

        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit with an invalid asset account name.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailNotAssetName(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([0, 'Fake name'])->andReturn(false);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_name' => 'Fake name',
                    'source_id'   => '0',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id'   => [
                        null,
                        'This value is invalid for this field.',
                    ],
                    'transactions.0.source_name' => [
                        null,
                    ],
                ],
            ]
        );
        $response->assertStatus(422);

        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Dont include enough repetitions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailRepetitions(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $validator  = $this->mock(AccountValidator::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([1, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([null, null])->andReturn(true);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'      => '100',
                    'currency_id' => '1',
                    'description' => 'Test description',
                    'source_id'   => '1',
                ],
            ],
            'repetitions'  => [],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'description' => [
                        'Need at least one repetition.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Dont include enough repetitions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreFailTransactions(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(AccountValidator::class);
        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'withdrawal',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'description' => [
                        'Need at least one transaction.',
                    ],
                ],
            ]
        );
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Submit a transfer. Since most validators have been tested in other methods, dont bother too much.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreTransfer(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        $assetAccount      = $this->getRandomAsset();
        $otherAssetAccount = $this->getRandomAsset($assetAccount->id);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([$assetAccount->id, null])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([$otherAssetAccount->id, null])->andReturn(true);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'transfer',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'description'    => 'Test description transfer',
                    'source_id'      => $assetAccount->id,
                    'destination_id' => $otherAssetAccount->id,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.recurrences.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Just a basic test because the store() tests cover everything.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceUpdateRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testUpdate(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository  = $this->mock(RecurringRepositoryInterface::class);
        $transformer = $this->mock(RecurrenceTransformer::class);
        $validator   = $this->mock(AccountValidator::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        // mock calls to validator:
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['deposit']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->withArgs([null, 'Some expense account'])->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->withArgs([1, null])->andReturn(true);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('update')->once()->andReturn($recurrence);

        // data to submit
        $firstDate = new Carbon;
        $firstDate->addDays(2);
        $data = [
            'type'         => 'deposit',
            'title'        => 'Hello',
            'first_date'   => $firstDate->format('Y-m-d'),
            'apply_rules'  => 1,
            'active'       => 1,
            'transactions' => [
                [
                    'amount'         => '100',
                    'currency_id'    => '1',
                    'description'    => 'Test description deposit',
                    'source_name'    => 'Some expense account',
                    'destination_id' => '1',
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => '0',
                    'weekend' => '1',

                ],
            ],
        ];

        // test API
        $response = $this->put(route('api.v1.recurrences.update', [$recurrence->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


}
