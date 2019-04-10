<?php
/**
 * TransactionJournalFactoryTest.php
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


use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class TransactionJournalFactoryTest
 */
class TransactionJournalFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testBudget(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $usd     = TransactionCurrency::whereCode('USD')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();
        $budget  = Budget::first();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id'             => $asset->id,
                    'amount'                => '1',
                    'foreign_currency_code' => 'USD',
                    'foreign_amount'        => '2',
                    'notes'                 => 'I am some notes',
                    'budget_id'             => $budget->id,
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, $budget->id, null])->once()->andReturn($budget);

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(1, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
        $this->assertEquals('I am some notes', $journal->notes->first()->text);
        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCategory(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type     = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro     = TransactionCurrency::whereCode('EUR')->first();
        $usd      = TransactionCurrency::whereCode('USD')->first();
        $asset    = $this->getRandomAsset();
        $category = Category::first();
        $expense  = $this->getRandomExpense();
        $budget   = Budget::first();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id'             => $asset->id,
                    'amount'                => '1',
                    'foreign_currency_code' => 'USD',
                    'foreign_amount'        => '2',
                    'notes'                 => 'I am some notes',
                    'budget_id'             => $budget->id,
                    'category_name'         => $category->name,
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, $budget->id, null])->once()->andReturn($budget);

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, $category->name])->once()->andReturn($category);

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(1, $journal->budgets);
        $this->assertCount(1, $journal->categories);
        $this->assertCount(2, $journal->transactions);
        $this->assertEquals('I am some notes', $journal->notes->first()->text);
        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCreateAlmostEmpty(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id' => $asset->id,
                    'amount'    => '1',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCreateAlmostEmptyTransfer(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type        = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $euro        = TransactionCurrency::whereCode('EUR')->first();
        $source      = $this->getRandomAsset();
        $destination = $this->getAnotherRandomAsset($source->id);

        // data to submit.
        $data = [
            'type'         => 'transfer',
            'transactions' => [
                // first transaction:
                [
                    'source_id'      => $source->id,
                    'destination_id' => $destination->id,
                    'amount'         => '1',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the piggy bank repository:
        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$source->id])->once()->andReturn($source);
        $accountRepos->shouldReceive('findNull')->withArgs([$destination->id])->once()->andReturn($destination);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCreateBasicGroup(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id' => $asset->id,
                    'amount'    => '1',
                ],
                // second transaction:
                [
                    'source_id' => $asset->id,
                    'amount'    => '1',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->times(2);

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->times(2)->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->times(2)->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->times(2)->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->times(2)->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->times(2)->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->times(2)->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();

        // collection should have two journals.
        $this->assertCount(2, $collection);

        // journal should have some props.
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);

        // group of journal should also have some props.
        /** @var TransactionGroup $group */
        $group = $journal->transactionGroups()->first();
        $this->assertCount(2, $group->transactionJournals);
        $this->assertEquals($journal->description, $group->title);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCreateEmpty(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $transactionFactory = $this->mock(TransactionFactory::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();

        // data to submit.
        $data = [];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $transactionFactory->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        $this->assertCount(0, $collection);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testCreatePiggyEvent(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type        = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $euro        = TransactionCurrency::whereCode('EUR')->first();
        $piggyBank   = PiggyBank::first();
        $source      = $this->getRandomAsset();
        $destination = $this->getAnotherRandomAsset($source->id);

        // data to submit.
        $data = [
            'type'         => 'transfer',
            'transactions' => [
                // first transaction:
                [
                    'source_id'       => $source->id,
                    'destination_id'  => $destination->id,
                    'amount'          => '1',
                    'piggy_bank_id'   => '1',
                    'piggy_bank_name' => 'Some name',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the piggy bank repository:
        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([null, 1, 'Some name'])->once()->andReturn($piggyBank);

        // calls to the piggy factory
        $eventFactory->shouldReceive('create')->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$source->id])->once()->andReturn($source);
        $accountRepos->shouldReceive('findNull')->withArgs([$destination->id])->once()->andReturn($destination);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testForeignCurrency(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $usd     = TransactionCurrency::whereCode('USD')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id'             => $asset->id,
                    'amount'                => '1',
                    'foreign_currency_code' => 'USD',
                    'foreign_amount'        => '2',
                    'notes'                 => 'I am some notes',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
        $this->assertEquals('I am some notes', $journal->notes->first()->text);
        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testNotes(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id' => $asset->id,
                    'amount'    => '1',
                    'notes'     => 'I am some notes',
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
        $this->assertEquals('I am some notes', $journal->notes->first()->text);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     */
    public function testTags(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock used repositories.
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
        $tagFactory      = $this->mock(TagFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);

        // data to return from various calls:
        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();
        $tag     = Tag::first();

        // data to submit.
        $data = [
            'transactions' => [
                // first transaction:
                [
                    'source_id' => $asset->id,
                    'amount'    => '1',
                    'tags'      => ['tagA', 'B', '', 'C'],
                ],
            ],
        ];

        // calls to setUser:
        $curRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();
        $piggyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        // calls to transaction type repository.
        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);

        // calls to the currency repository:
        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);

        // calls to the bill repository:
        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the budget repository
        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the category repository
        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();

        // calls to the account repository
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);

        // calls to tag factory
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['tagA'])->andReturn($tag);
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['B'])->andReturn($tag);
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['C'])->andReturnNull();

        // calls to the meta factory:
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        $collection = $factory->create($data);

        /** @var TransactionJournal $journal */
        $journal = $collection->first();
        // collection should have one journal.
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(TransactionJournal::class, $journal);
        $this->assertEquals('(empty description)', $journal->description);
        $this->assertCount(0, $journal->budgets);
        $this->assertCount(0, $journal->categories);
        $this->assertCount(2, $journal->transactions);
        $this->assertCount(1, $journal->tags); // we return the same tag every time.
    }

    /**
     * @param int $id
     *
     * @return Account
     */
    private function getAnotherRandomAsset(int $id): Account
    {

        $query = Account::
        leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                        ->whereNull('accounts.deleted_at')
                        ->where('accounts.user_id', $this->user()->id)
                        ->where('account_types.type', AccountType::ASSET)
                        ->where('accounts.id', '!=', $id)
                        ->inRandomOrder()->take(1);

        return $query->first(['accounts.*']);
    }

}
