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
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

class RecurrenceControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get a recurrence:
        $recurrence = $this->user()->recurrences()->first();

        // call API
        $response = $this->delete('/api/v1/recurrences/' . $recurrence->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     */
    public function testIndex(): void
    {
        /** @var Recurrence $recurrences */
        $recurrences = $this->user()->recurrences()->get();

        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getAll')->once()->andReturn($recurrences);
        $repository->shouldReceive('getNoteText')->andReturn('Notes.');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);


        // call API
        $response = $this->get('/api/v1/recurrences');
        $response->assertStatus(200);
        $response->assertSee($recurrences->first()->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     */
    public function testShow(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository = $this->mock(RecurringRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getNoteText')->andReturn('Notes.');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // call API
        $response = $this->get('/api/v1/recurrences/' . $recurrence->id);
        $response->assertStatus(200);
        $response->assertSee($recurrence->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source ID field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreAssetId(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Submit the minimum amount to store a recurring transaction (using source name field).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreAssetName(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[0]])->once()->andReturn(new Collection);
        // used by the validator to find the source_name:
        $accountRepos->shouldReceive('findByName')->withArgs(['Checking Account', [AccountType::ASSET]])->once()->andReturn($assetAccount);

        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Submit a deposit. Since most validators have been tested in other methods, dont bother too much.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreDeposit(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);


        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Add a recurring with correct reference to a destination (expense).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreDestinationId(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expenseAccount = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[$expenseAccount->id]])->once()
                     ->andReturn(new Collection([$expenseAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Add a recurring with correct reference to a destination (expense).
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreDestinationName(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expenseAccount = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Includes both repetition count and an end date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailBothRepetitions(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailForeignCurrency(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[0]])->once()->andReturn(new Collection);
        // used by the validator to find the source_name:
        $accountRepos->shouldReceive('findByName')->withArgs(['Checking Account', [AccountType::ASSET]])->once()->andReturn($assetAccount);

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidDaily(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidDestinationId(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[$assetAccount->id]])->once()
                     ->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.destination_id' => [
                        'This value is invalid for this field.',
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidMonthly(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidNdom(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidNdomCount(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidNdomHigh(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailInvalidWeekly(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailNoAsset(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id' => [
                        'This value is invalid for this field.',
                        'The transactions.0.source_id field is required.',
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailNotAsset(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // expense account:
        $expenseAccount = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[$expenseAccount->id]])->once()
                     ->andReturn(new Collection([$expenseAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id' => [
                        'This value is invalid for this field.',
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailNotAssetName(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // expense account:
        $expenseAccount = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[0]])->once()
                     ->andReturn(new Collection);
        // used to search by name.
        $accountRepos->shouldReceive('findByName')->withArgs(['Fake name', [AccountType::ASSET]])->once()
                     ->andReturn(null);

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id'   => [
                        'This value is invalid for this field.',
                    ],
                    'transactions.0.source_name' => [
                        'This value is invalid for this field.',
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailRepetitions(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()
                     ->andReturn(new Collection([$assetAccount]));

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreFailTransactions(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');

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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testStoreTransfer(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $otherAssetAccount = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $assetAccount->id)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($recurrence);


        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[$assetAccount->id]])->once()->andReturn(new Collection([$assetAccount]));
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[$otherAssetAccount->id]])->once()->andReturn(new Collection([$otherAssetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->post('/api/v1/recurrences', $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Just a basic test because the store() tests cover everything.
     *
     * @covers \FireflyIII\Api\V1\Controllers\RecurrenceController
     * @covers \FireflyIII\Api\V1\Requests\RecurrenceRequest
     */
    public function testUpdate(): void
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->user()->recurrences()->first();

        // mock stuff:
        $repository   = $this->mock(RecurringRepositoryInterface::class);
        $factory      = $this->mock(CategoryFactory::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $assetAccount = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $factory->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn($recurrence);


        // used by the validator to find the source_id:
        $accountRepos->shouldReceive('getAccountsById')->withArgs([[1]])->once()->andReturn(new Collection([$assetAccount]));


        // entries used by the transformer
        $repository->shouldReceive('getNoteText')->andReturn('Note text');
        $repository->shouldReceive('repetitionDescription')->andReturn('Some description.');
        $repository->shouldReceive('getXOccurrences')->andReturn([]);

        // entries used by the transformer (the fake entry has a category + a budget):
        $factory->shouldReceive('findOrCreate')->andReturn(null);
        $budgetRepos->shouldReceive('findNull')->andReturn(null);


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
        $response = $this->put('/api/v1/recurrences/' . $recurrence->id, $data, ['Accept' => 'application/json']);
        $response->assertSee($recurrence->title);
        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


}
