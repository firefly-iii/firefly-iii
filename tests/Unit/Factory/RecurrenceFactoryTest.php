<?php
/**
 * RecurrenceFactoryTest.php
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

namespace Tests\Unit\Factory;


use Amount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Factory\RecurrenceFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionTypeFactory;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Validation\AccountValidator;
use Log;
use Tests\TestCase;

/**
 *
 *
 * Test different combinations:
 * Transfer
 * Withdrawal
 * Deposit
 *
 * With the correct types.
 *
 * Class RecurrenceFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurrenceFactoryTest extends TestCase
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
     * With piggy bank. With tags. With budget. With category.
     * This is a withdrawal
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreate(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomExpense();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();
        $euro        = $this->getEuro();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();
        $piggyFactory->shouldReceive('setUser')->atLeast()->once();
        $budgetFactory->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->twice();
        $categoryFactory->shouldReceive('setUser')->once();


        $piggyFactory->shouldReceive('find')->atLeast()->once()->withArgs([1, 'Bla bla'])->andReturn($piggyBank);
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($source, $destination);
        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some other name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * With piggy bank. With tags. With budget. With category.
     * Submit account names, not types. This is a withdrawal.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateByName(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomExpense();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();
        $euro        = $this->getEuro();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();
        $piggyFactory->shouldReceive('setUser')->atLeast()->once();
        $budgetFactory->shouldReceive('setUser')->atLeast()->once();
        $categoryFactory->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->twice();
        //$accountFactory->shouldReceive('setUser')->atLeast()->once();

        $piggyFactory->shouldReceive('find')->atLeast()->once()->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        // return NULL for account ID's.
        $accountRepos->shouldReceive('findNull')->twice()->andReturn(null, null);
        // but find them by name:
        $accountRepos->shouldReceive('findByName')->twice()->andReturn($source, $destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some other name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * With piggy bank. With tags. With budget. With category.
     * Submit account names, not types. Also a withdrawal
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateNewByName(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomExpense();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();
        $euro        = $this->getEuro();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();
        $piggyFactory->shouldReceive('setUser')->atLeast()->once();
        $budgetFactory->shouldReceive('setUser')->atLeast()->once();
        $categoryFactory->shouldReceive('setUser')->once();

        $accountRepos->shouldReceive('setUser')->twice();
        $piggyFactory->shouldReceive('find')->atLeast()->once()->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        // return NULL for account ID's.
        $accountRepos->shouldReceive('findNull')->twice()->andReturn(null, null);
        // but find them by name (at least the first one):
        $accountRepos->shouldReceive('findByName')->twice()->andReturn($source, null);

        // this activates the "create by name" routine (account factory):
        $accountFactory->shouldReceive('setUser')->atLeast()->once();
        $accountFactory->shouldReceive('findOrCreate')->atLeast()->once()
                       ->andReturn($destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);


        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some other name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }


    /**
     * Deposit. With piggy bank. With tags. With budget. With category.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateDeposit(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($this->getEuro())->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($source, $destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'deposit',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some otjer name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];


        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(2));

        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * No piggy bank. With tags. With budget. With category. Withdrawal.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateNoPiggybank(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomExpense();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($this->getEuro())->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn(null);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($source, $destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some otjer name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];

        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * With piggy bank. With tags. With budget. With category. Withdrawal
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateNoTags(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomExpense();
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $validator       = $this->mock(AccountValidator::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($this->getEuro())->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($source, $destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);


        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => [],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some otjer name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];

        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * Deposit. With piggy bank. With tags. With budget. With category.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testCreateTransfer(): void
    {
        // objects to return:
        $piggyBank   = $this->user()->piggyBanks()->inRandomOrder()->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomAsset($source->id);
        $budget      = $this->user()->budgets()->inRandomOrder()->first();
        $category    = $this->user()->categories()->inRandomOrder()->first();
        $validator   = $this->mock(AccountValidator::class);

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $typeFactory     = $this->mock(TransactionTypeFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($this->getEuro())->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($source, $destination);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // validator:
        $validator->shouldReceive('setUser')->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'transfer',
                'first_date'   => Carbon::now()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . $this->randomInt(),
                'description'  => 'Description thing',
                'apply_rules'  => true,
                'active'       => true,
                'repeat_until' => null,
            ],
            'meta'         => [
                'tags'            => ['a', 'b', 'c'],
                'piggy_bank_id'   => 1,
                'piggy_bank_name' => 'Bla bla',
            ],
            'repetitions'  => [
                [
                    'type'    => 'daily',
                    'moment'  => '',
                    'skip'    => 0,
                    'weekend' => 1,
                ],
            ],
            'transactions' => [
                [
                    'source_id'             => 1,
                    'source_name'           => 'Some name',
                    'destination_id'        => 2,
                    'destination_name'      => 'some otjer name',
                    'currency_id'           => 1,
                    'currency_code'         => 'EUR',
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'description'           => 'Bla bla bla',
                    'amount'                => '100',
                    'budget_id'             => 1,
                    'budget_name'           => 'Some budget',
                    'category_id'           => 2,
                    'category_name'         => 'Some category',

                ],
            ],
        ];


        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(3));

        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);

        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * @covers \FireflyIII\Factory\RecurrenceFactory
     */
    public function testCreateBadTransactionType(): void
    {
        $accountFactory = $this->mock(AccountFactory::class);
        $validator      = $this->mock(AccountValidator::class);
        $typeFactory    = $this->mock(TransactionTypeFactory::class);
        $data           = [
            'recurrence' => [
                'type' => 'bad type',
            ],
        ];


        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(null);


        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user());
        $result = null;
        try {
            $result = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertEquals('Cannot make a recurring transaction of type "bad type"', $e->getMessage());
            $this->assertTrue(true);
        }
        $this->assertNull($result);
    }

}
