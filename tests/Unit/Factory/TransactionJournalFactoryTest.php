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


use Amount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
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
     * Submit empty array.
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreate(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $submission         = [];

        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(0, $collection);
    }

    /**
     * Submit minimal array for a withdrawal.
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateWithdrawal(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $euro       = $this->getEuro();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'withdrawal',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $expense->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'withdrawal'])->atLeast()->once()->andReturn($withdrawal);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$expense->id])->atLeast()->once()->andReturn($expense);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $budgetRepos->shouldReceive('findBudget')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }


    /**
     * Submit minimal array for a deposit.
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateDeposit(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $deposit = TransactionType::where('type', TransactionType::DEPOSIT)->first();
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $revenue = $this->getRandomRevenue();
        $euro       = $this->getEuro();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'deposit',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $revenue->id,
                    'destination_id' => $asset->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'deposit'])->atLeast()->once()->andReturn($deposit);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$revenue->id])->atLeast()->once()->andReturn($revenue);
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }


    /**
     * Submit array for a withdrawal. Include tag info.
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateWithdrawalTags(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $asset      = $this->getRandomAsset();
        $tag = $this->user()->tags()->inRandomOrder()->first();
        $expense    = $this->getRandomExpense();
        $euro       = $this->getEuro();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'withdrawal',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $expense->id,
                    'tags'           => ['a'],
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'withdrawal'])->atLeast()->once()->andReturn($withdrawal);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$expense->id])->atLeast()->once()->andReturn($expense);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $budgetRepos->shouldReceive('findBudget')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();
        $tagFactory->shouldReceive('findOrCreate')->atLeast()->once()->andReturn($tag);


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }


    /**
     * Submit minimal array for a withdrawal.
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateWithdrawalNote(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $euro       = $this->getEuro();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'withdrawal',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $expense->id,
                    'notes'          => 'I am a note',
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'withdrawal'])->atLeast()->once()->andReturn($withdrawal);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$expense->id])->atLeast()->once()->andReturn($expense);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $budgetRepos->shouldReceive('findBudget')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $this->assertCount(1, $first->notes()->get());
        $first->forceDelete();
    }

    /**
     * Submit array for a withdrawal.
     *
     * Include budget, category.
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateWithdrawalMeta(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $budget     = $this->user()->budgets()->inRandomOrder()->first();
        $category   = $this->user()->categories()->inRandomOrder()->first();
        $euro       = $this->getEuro();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'withdrawal',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'budget_id'      => $budget->id,
                    'category_id'    => $category->id,
                    'destination_id' => $expense->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'withdrawal'])->atLeast()->once()->andReturn($withdrawal);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$expense->id])->atLeast()->once()->andReturn($expense);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $budgetRepos->shouldReceive('findBudget')->withArgs([$budget->id, null])->atLeast()->once()->andReturn($budget);
        $catRepos->shouldReceive('findCategory')->withArgs([$category->id, null])->atLeast()->once()->andReturn($category);
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }

    /**
     * Submit minimal array for a withdrawal.
     * Includes piggy bank data, but the piggy bank is invalid.
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateTransferInvalidPiggie(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $transfer   = TransactionType::where('type', TransactionType::TRANSFER)->first();
        $asset      = $this->getRandomAsset();
        $otherAsset = $this->getRandomAsset($asset->id);
        $euro       = $this->getEuro();
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'transfer',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $otherAsset->id,
                    'piggy_bank_id'  => $piggy->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->atLeast()->once()->andReturn($transfer);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$otherAsset->id])->atLeast()->once()->andReturn($otherAsset);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();
        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([$piggy->id, null])->atLeast()->once()->andReturn(null);


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }


    /**
     * Submit minimal array for a withdrawal.
     * Includes piggy bank data.
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateTransfer(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $transfer   = TransactionType::where('type', TransactionType::TRANSFER)->first();
        $asset      = $this->getRandomAsset();
        $otherAsset = $this->getRandomAsset($asset->id);
        $euro       = $this->getEuro();
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'transfer',
                    'amount'         => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $otherAsset->id,
                    'piggy_bank_id'  => $piggy->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->atLeast()->once()->andReturn($transfer);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$otherAsset->id])->atLeast()->once()->andReturn($otherAsset);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();
        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([$piggy->id, null])->atLeast()->once()->andReturn($piggy);
        $eventFactory->shouldReceive('create')->atLeast()->once()->andReturn(new PiggyBankEvent);


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }


    /**
     * Submit minimal array for a withdrawal.
     * Includes piggy bank data.
     * Includes foreign amounts but foreign currency is missing or invalid.
     * Will be solved by getting users default currency.
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateTransferForeign(): void
    {
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $accountFactory     = $this->mock(AccountFactory::class);
        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);


        // data
        $transfer   = TransactionType::where('type', TransactionType::TRANSFER)->first();
        $asset      = $this->getRandomAsset();
        $otherAsset = $this->getRandomAsset($asset->id);
        $euro       = $this->getEuro();
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $submission = [
            'transactions' => [
                [
                    'type'           => 'transfer',
                    'amount'         => '10',
                    'foreign_amount' => '10',
                    'description'    => sprintf('I am a test #%d', $this->randomInt()),
                    'source_id'      => $asset->id,
                    'destination_id' => $otherAsset->id,
                    'piggy_bank_id'  => $piggy->id,
                ],
            ],
        ];


        // mock calls to all repositories
        $curRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();
        $catRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $tagFactory->shouldReceive('setUser')->atLeast()->once();
        $transactionFactory->shouldReceive('setUser')->atLeast()->once();

        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($euro);

        $transactionFactory->shouldReceive('setJournal')->atLeast()->once();
        $transactionFactory->shouldReceive('setAccount')->atLeast()->once();
        $transactionFactory->shouldReceive('setCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setForeignCurrency')->atLeast()->once();
        $transactionFactory->shouldReceive('setReconciled')->atLeast()->once();
        $transactionFactory->shouldReceive('createNegative')->atLeast()->once()->andReturn(new Transaction);
        $transactionFactory->shouldReceive('createPositive')->atLeast()->once()->andReturn(new Transaction);

        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->atLeast()->once()->andReturn($transfer);
        $curRepos->shouldReceive('findCurrency')->atLeast()->once()->withArgs([0, null])->andReturn($euro);
        $curRepos->shouldReceive('findCurrencyNull')->atLeast()->once()->withArgs([0, null])->andReturnNull();
        $billRepos->shouldReceive('findBill')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->atLeast()->once()->andReturn($asset);
        $accountRepos->shouldReceive('findNull')->withArgs([$otherAsset->id])->atLeast()->once()->andReturn($otherAsset);
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro, null);
        $catRepos->shouldReceive('findCategory')->withArgs([0, null])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once();
        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([$piggy->id, null])->atLeast()->once()->andReturn($piggy);
        $eventFactory->shouldReceive('create')->atLeast()->once()->andReturn(new PiggyBankEvent);


        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }
        $this->assertCount(1, $collection);
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $this->assertEquals($first->description, $submission['transactions'][0]['description']);
        $first->forceDelete();
    }



//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testBudget(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $usd     = TransactionCurrency::whereCode('USD')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//        $budget  = Budget::first();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id'             => $asset->id,
//                    'amount'                => '1',
//                    'foreign_currency_code' => 'USD',
//                    'foreign_amount'        => '2',
//                    'notes'                 => 'I am some notes',
//                    'budget_id'             => $budget->id,
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, $budget->id, null])->once()->andReturn($budget);
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(1, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//        $this->assertEquals('I am some notes', $journal->notes->first()->text);
//        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
//        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCategory(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type     = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro     = TransactionCurrency::whereCode('EUR')->first();
//        $usd      = TransactionCurrency::whereCode('USD')->first();
//        $asset    = $this->getRandomAsset();
//        $category = Category::first();
//        $expense  = $this->getRandomExpense();
//        $budget   = Budget::first();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id'             => $asset->id,
//                    'amount'                => '1',
//                    'foreign_currency_code' => 'USD',
//                    'foreign_amount'        => '2',
//                    'notes'                 => 'I am some notes',
//                    'budget_id'             => $budget->id,
//                    'category_name'         => $category->name,
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, $budget->id, null])->once()->andReturn($budget);
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, $category->name])->once()->andReturn($category);
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(1, $journal->budgets);
//        $this->assertCount(1, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//        $this->assertEquals('I am some notes', $journal->notes->first()->text);
//        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
//        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCreateAlmostEmpty(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id' => $asset->id,
//                    'amount'    => '1',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCreateAlmostEmptyTransfer(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type        = TransactionType::whereType(TransactionType::TRANSFER)->first();
//        $euro        = TransactionCurrency::whereCode('EUR')->first();
//        $source      = $this->getRandomAsset();
//        $destination = $this->getAnotherRandomAsset($source->id);
//
//        // data to submit.
//        $data = [
//            'type'         => 'transfer',
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id'      => $source->id,
//                    'destination_id' => $destination->id,
//                    'amount'         => '1',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the piggy bank repository:
//        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$source->id])->once()->andReturn($source);
//        $accountRepos->shouldReceive('findNull')->withArgs([$destination->id])->once()->andReturn($destination);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCreateBasicGroup(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id' => $asset->id,
//                    'amount'    => '1',
//                ],
//                // second transaction:
//                [
//                    'source_id' => $asset->id,
//                    'amount'    => '1',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->times(2);
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->times(2)->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->times(2)->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->times(2)->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->times(2)->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->times(2)->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->times(2)->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//
//        // collection should have two journals.
//        $this->assertCount(2, $collection);
//
//        // journal should have some props.
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//
//        // group of journal should also have some props.
//        /** @var TransactionGroup $group */
//        $group = $journal->transactionGroups()->first();
//        $this->assertCount(2, $group->transactionJournals);
//        $this->assertEquals($journal->description, $group->title);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCreateEmpty(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos          = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos           = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos           = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos         = $this->mock(PiggyBankRepositoryInterface::class);
//        $transactionFactory = $this->mock(TransactionFactory::class);
//        $typeRepos          = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory         = $this->mock(TagFactory::class);
//        $accountFactory     = $this->mock(AccountFactory::class);
//        $currencyFactory    = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//
//        // data to submit.
//        $data = [];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $transactionFactory->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        $this->assertCount(0, $collection);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testCreatePiggyEvent(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type        = TransactionType::whereType(TransactionType::TRANSFER)->first();
//        $euro        = TransactionCurrency::whereCode('EUR')->first();
//        $piggyBank   = PiggyBank::first();
//        $source      = $this->getRandomAsset();
//        $destination = $this->getAnotherRandomAsset($source->id);
//
//        // data to submit.
//        $data = [
//            'type'         => 'transfer',
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id'       => $source->id,
//                    'destination_id'  => $destination->id,
//                    'amount'          => '1',
//                    'piggy_bank_id'   => '1',
//                    'piggy_bank_name' => 'Some name',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, 'transfer'])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the piggy bank repository:
//        $piggyRepos->shouldReceive('findPiggyBank')->withArgs([null, 1, 'Some name'])->once()->andReturn($piggyBank);
//
//        // calls to the piggy factory
//        $eventFactory->shouldReceive('create')->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$source->id])->once()->andReturn($source);
//        $accountRepos->shouldReceive('findNull')->withArgs([$destination->id])->once()->andReturn($destination);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testForeignCurrency(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $usd     = TransactionCurrency::whereCode('USD')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id'             => $asset->id,
//                    'amount'                => '1',
//                    'foreign_currency_code' => 'USD',
//                    'foreign_amount'        => '2',
//                    'notes'                 => 'I am some notes',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, 'USD'])->once()->andReturn($usd);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//        $this->assertEquals('I am some notes', $journal->notes->first()->text);
//        $this->assertEquals('EUR', $journal->transactions->first()->transactionCurrency->code);
//        $this->assertEquals('USD', $journal->transactions->first()->foreignCurrency->code);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testNotes(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id' => $asset->id,
//                    'amount'    => '1',
//                    'notes'     => 'I am some notes',
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//        $this->assertEquals('I am some notes', $journal->notes->first()->text);
//    }
//
//    /**
//     * @covers \FireflyIII\Factory\TransactionJournalFactory
//     */
//    public function testTags(): void
//    {
//        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');
//
//        return;
//        // mock used repositories.
//        $billRepos       = $this->mock(BillRepositoryInterface::class);
//        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
//        $catRepos        = $this->mock(CategoryRepositoryInterface::class);
//        $curRepos        = $this->mock(CurrencyRepositoryInterface::class);
//        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
//        $typeRepos       = $this->mock(TransactionTypeRepositoryInterface::class);
//        $eventFactory    = $this->mock(PiggyBankEventFactory::class);
//        $tagFactory      = $this->mock(TagFactory::class);
//        $accountFactory  = $this->mock(AccountFactory::class);
//        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
//        $metaFactory     = $this->mock(TransactionJournalMetaFactory::class);
//        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
//
//        // data to return from various calls:
//        $type    = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
//        $euro    = TransactionCurrency::whereCode('EUR')->first();
//        $asset   = $this->getRandomAsset();
//        $expense = $this->getRandomExpense();
//        $tag     = Tag::first();
//
//        // data to submit.
//        $data = [
//            'transactions' => [
//                // first transaction:
//                [
//                    'source_id' => $asset->id,
//                    'amount'    => '1',
//                    'tags'      => ['tagA', 'B', '', 'C'],
//                ],
//            ],
//        ];
//
//        // calls to setUser:
//        $curRepos->shouldReceive('setUser')->once();
//        $billRepos->shouldReceive('setUser')->once();
//        $budgetRepos->shouldReceive('setUser')->once();
//        $catRepos->shouldReceive('setUser')->once();
//        $piggyRepos->shouldReceive('setUser')->once();
//        $accountRepos->shouldReceive('setUser')->once();
//        $tagFactory->shouldReceive('setUser')->once();
//
//        // calls to transaction type repository.
//        $typeRepos->shouldReceive('findTransactionType')->withArgs([null, null])->once()->andReturn($type);
//
//        // calls to the currency repository:
//        $curRepos->shouldReceive('findCurrency')->withArgs([null, null, null])->once()->andReturn($euro);
//
//        // calls to the bill repository:
//        $billRepos->shouldReceive('findBill')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the budget repository
//        $budgetRepos->shouldReceive('findBudget')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the category repository
//        $catRepos->shouldReceive('findCategory')->withArgs([null, null, null])->once()->andReturnNull();
//
//        // calls to the account repository
//        $accountRepos->shouldReceive('findNull')->withArgs([$asset->id])->once()->andReturn($asset);
//        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($expense);
//
//        // calls to tag factory
//        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['tagA'])->andReturn($tag);
//        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['B'])->andReturn($tag);
//        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs(['C'])->andReturnNull();
//
//        // calls to the meta factory:
//        $metaFactory->shouldReceive('updateOrCreate')->atLeast()->once()->andReturnNull();
//
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($this->user());
//        $collection = $factory->create($data);
//
//        /** @var TransactionJournal $journal */
//        $journal = $collection->first();
//        // collection should have one journal.
//        $this->assertCount(1, $collection);
//        $this->assertInstanceOf(TransactionJournal::class, $journal);
//        $this->assertEquals('(empty description)', $journal->description);
//        $this->assertCount(0, $journal->budgets);
//        $this->assertCount(0, $journal->categories);
//        $this->assertCount(2, $journal->transactions);
//        $this->assertCount(1, $journal->tags); // we return the same tag every time.
//    }
//
//    /**
//     * @param int $id
//     *
//     * @return Account
//     */
//    private function getAnotherRandomAsset(int $id): Account
//    {
//
//        $query = Account::
//        leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
//                        ->whereNull('accounts.deleted_at')
//                        ->where('accounts.user_id', $this->user()->id)
//                        ->where('account_types.type', AccountType::ASSET)
//                        ->where('accounts.id', '!=', $id)
//                        ->inRandomOrder()->take(1);
//
//        return $query->first(['accounts.*']);
//    }

}
