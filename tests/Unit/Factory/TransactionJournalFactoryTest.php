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
use FireflyIII\Validation\AccountValidator;
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
        $validator = $this->mock(AccountValidator::class);
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
        $validator = $this->mock(AccountValidator::class);


        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();



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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['deposit']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

        // data
        $deposit = TransactionType::where('type', TransactionType::DEPOSIT)->first();
        $asset      = $this->getRandomAsset();
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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['withdrawal']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
        $validator = $this->mock(AccountValidator::class);

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturnTrue();
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturnTrue();

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
}
