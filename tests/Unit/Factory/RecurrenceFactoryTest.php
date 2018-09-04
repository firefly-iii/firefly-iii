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
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Factory\RecurrenceFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionTypeFactory;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 *
 * Class RecurrenceFactoryTest
 */
class RecurrenceFactoryTest extends TestCase
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
     * With piggy bank. With tags. With budget. With category.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testBasic(): void
    {
        // objects to return:
        $piggyBank = $this->user()->piggyBanks()->inRandomOrder()->first();
        $accountA  = $this->user()->accounts()->inRandomOrder()->first();
        $accountB  = $this->user()->accounts()->inRandomOrder()->first();
        $budget    = $this->user()->budgets()->inRandomOrder()->first();
        $category  = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn(TransactionCurrency::find(1))->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($accountA, $accountB);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // data for basic recurrence.
        $data        = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::create()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . random_int(1, 100000),
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
        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        $factory = new RecurrenceFactory;
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
    public function testBasicDeposit(): void
    {
        // objects to return:
        $piggyBank = $this->user()->piggyBanks()->inRandomOrder()->first();
        $accountA  = $this->user()->accounts()->inRandomOrder()->first();
        $accountB  = $this->user()->accounts()->inRandomOrder()->first();
        $budget    = $this->user()->budgets()->inRandomOrder()->first();
        $category  = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn(TransactionCurrency::find(1))->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($accountA, $accountB);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'deposit',
                'first_date'   => Carbon::create()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . random_int(1, 100000),
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

        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(2));

        $factory = new RecurrenceFactory;
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * No piggy bank. With tags. With budget. With category.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testBasicNoPiggybank(): void
    {
        // objects to return:
        $piggyBank = $this->user()->piggyBanks()->inRandomOrder()->first();
        $accountA  = $this->user()->accounts()->inRandomOrder()->first();
        $accountB  = $this->user()->accounts()->inRandomOrder()->first();
        $budget    = $this->user()->budgets()->inRandomOrder()->first();
        $category  = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn(TransactionCurrency::find(1))->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn(null);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($accountA, $accountB);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // data for basic recurrence.
        $data        = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::create()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . random_int(1, 100000),
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
        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        $factory = new RecurrenceFactory;
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * With piggy bank. With tags. With budget. With category.
     *
     * @covers \FireflyIII\Factory\RecurrenceFactory
     * @covers \FireflyIII\Services\Internal\Support\RecurringTransactionTrait
     */
    public function testBasicNoTags(): void
    {
        // objects to return:
        $piggyBank = $this->user()->piggyBanks()->inRandomOrder()->first();
        $accountA  = $this->user()->accounts()->inRandomOrder()->first();
        $accountB  = $this->user()->accounts()->inRandomOrder()->first();
        $budget    = $this->user()->budgets()->inRandomOrder()->first();
        $category  = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn(TransactionCurrency::find(1))->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($accountA, $accountB);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // data for basic recurrence.
        $data        = [
            'recurrence'   => [
                'type'         => 'withdrawal',
                'first_date'   => Carbon::create()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . random_int(1, 100000),
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
        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(1));
        $factory = new RecurrenceFactory;
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
    public function testBasicTransfer(): void
    {
        // objects to return:
        $piggyBank = $this->user()->piggyBanks()->inRandomOrder()->first();
        $accountA  = $this->user()->accounts()->inRandomOrder()->first();
        $accountB  = $this->user()->accounts()->inRandomOrder()->first();
        $budget    = $this->user()->budgets()->inRandomOrder()->first();
        $category  = $this->user()->categories()->inRandomOrder()->first();

        // mock other factories:
        $piggyFactory    = $this->mock(PiggyBankFactory::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn(TransactionCurrency::find(1))->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('find')->withArgs([1, 'Bla bla'])->andReturn($piggyBank);

        $accountRepos->shouldReceive('setUser')->twice();
        $accountRepos->shouldReceive('findNull')->twice()->andReturn($accountA, $accountB);

        $currencyFactory->shouldReceive('find')->once()->withArgs([1, 'EUR'])->andReturn(null);
        $currencyFactory->shouldReceive('find')->once()->withArgs([null, null])->andReturn(null);

        $budgetFactory->shouldReceive('setUser')->once();
        $budgetFactory->shouldReceive('find')->withArgs([1, 'Some budget'])->once()->andReturn($budget);

        $categoryFactory->shouldReceive('setUser')->once();
        $categoryFactory->shouldReceive('findOrCreate')->withArgs([2, 'Some category'])->once()->andReturn($category);

        // data for basic recurrence.
        $data = [
            'recurrence'   => [
                'type'         => 'transfer',
                'first_date'   => Carbon::create()->addDay(),
                'repetitions'  => 0,
                'title'        => 'Test recurrence' . random_int(1, 100000),
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

        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(TransactionType::find(3));

        $factory = new RecurrenceFactory;
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertEquals($result->title, $data['recurrence']['title']);
    }

    /**
     * @covers \FireflyIII\Factory\RecurrenceFactory
     */
    public function testCreateBadTransactionType(): void
    {
        $data = [
            'recurrence' => [
                'type' => 'bad type',
            ],
        ];

        $typeFactory = $this->mock(TransactionTypeFactory::class);
        $typeFactory->shouldReceive('find')->once()->withArgs([ucfirst($data['recurrence']['type'])])->andReturn(null);


        $factory = new RecurrenceFactory;
        $factory->setUser($this->user());

        $result = $factory->create($data);
        $this->assertNull($result);
    }

}