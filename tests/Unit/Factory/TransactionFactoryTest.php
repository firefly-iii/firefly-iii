<?php
/**
 * TransactionFactoryTest.php
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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class TransactionFactoryTest
 */
class TransactionFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairBasic(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $expense);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($withdrawal->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairBasicByName(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => null,
            'source_name'           => $asset->name,
            'destination_id'        => null,
            'destination_name'      => $expense->name,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        $accountFactory->shouldReceive('setUser');
        // first search action is for the asset account
        $accountRepos->shouldReceive('findByName')->withArgs([$asset->name, [AccountType::ASSET]])->andReturn($asset);
        // second is for expense account.
        $accountFactory->shouldReceive('findOrCreate')->andReturn($expense);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($withdrawal->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairBasicIntoCash(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => null,
            'source_name'           => $asset->name,
            'destination_id'        => null,
            'destination_name'      => '',
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        $accountFactory->shouldReceive('setUser');
        // first search action is for the asset account
        $accountRepos->shouldReceive('findByName')->withArgs([$asset->name, [AccountType::ASSET]])->andReturn($asset);
        // second is for expense account (cash)
        $accountRepos->shouldReceive('getCashAccount')->andReturn($expense);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $newCount = $withdrawal->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($withdrawal->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * Add budget and category data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairBasicMeta(): void
    {
        // objects:
        $asset    = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense  = $this->user()->accounts()->where('account_type_id', 4)->first();
        $budget   = $this->user()->budgets()->first();
        $category = $this->user()->categories()->first();
        $euro     = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => $budget->id,
            'budget_name'           => null,
            'category_id'           => $category->id,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $expense);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn($budget);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn($category);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $newCount = $withdrawal->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($withdrawal->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
        $this->assertEquals($budget->name, $first->budgets()->first()->name);
        $this->assertEquals($category->name, $first->categories()->first()->name);
    }

    /**
     * Create deposit using minimal data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairDeposit(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $revenue = $this->user()->accounts()->where('account_type_id', 5)->first();
        $euro    = TransactionCurrency::first();
        $foreign = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $revenue->id,
            'source_name'           => null,
            'destination_id'        => $asset->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($revenue, $asset);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $count   = $deposit->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($deposit, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $newCount = $deposit->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($deposit->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * Create deposit using minimal data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairDepositByName(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $revenue = $this->user()->accounts()->where('account_type_id', 5)->first();
        $euro    = TransactionCurrency::first();
        $foreign = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => null,
            'source_name'           => $revenue->name,
            'destination_id'        => $asset->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        $accountFactory->shouldReceive('setUser');
        // first search action is for the asset account
        $accountRepos->shouldReceive('findNull')->andReturn($asset);
        // second is for revenue account.
        $accountFactory->shouldReceive('findOrCreate')->andReturn($revenue);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $count   = $deposit->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($deposit, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $deposit->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($deposit->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * Create deposit using minimal data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairDepositCash(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $revenue = $this->user()->accounts()->where('account_type_id', 5)->first();
        $euro    = TransactionCurrency::first();
        $foreign = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $accountFactory  = $this->mock(AccountFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => null,
            'source_name'           => '',
            'destination_id'        => $asset->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        $accountFactory->shouldReceive('setUser');
        // first search action is for the asset account
        $accountRepos->shouldReceive('findNull')->andReturn($asset);

        // second is for revenue account.
        $accountRepos->shouldReceive('getCashAccount')->andReturn($revenue)->once();

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $count   = $deposit->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($deposit, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $deposit->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($deposit->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairEmptyAmount(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'destination_name'      => null,
            'amount'                => '',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $expense)->atLeast()->once();

        // factories return various stuff:
        $currencyFactory->shouldReceive('find')->andReturn($euro, null)->atLeast()->once();

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertEquals('Amount is an empty string, which Firefly III cannot handle. Apologies.', $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();
        $this->assertEquals($count, $newCount);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairForeign(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();
        $foreign = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => $euro->id,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => $foreign->id,
            'foreign_currency_code' => null,
            'foreign_amount'        => '10',
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $expense);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, $foreign);

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $newCount = $withdrawal->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($withdrawal->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(-10, $first->foreign_amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertEquals($foreign->id, $first->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairNoAccounts(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn(null, null)->atLeast()->once();

        // factories return various stuff:
        $currencyFactory->shouldReceive('find')->andReturn($euro, null)->atLeast()->once();

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertEquals('Could not determine source or destination account.', $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();

        $this->assertEquals($count, $newCount);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairNoCurrency(): void
    {
        // objects:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $expense->id,
            'currency_id'           => null,
            'currency_code'         => null,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $expense)->atLeast()->once();

        // factories return various stuff:
        $currencyFactory->shouldReceive('find')->andReturn(null, null)->atLeast()->once();

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertEquals('Cannot store transaction without currency information.', $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();

        $this->assertEquals($count, $newCount);
    }

    /**
     * Create reconciliation using minimal data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairReconciliation(): void
    {
        // objects:
        $asset        = $this->user()->accounts()->where('account_type_id', 3)->first();
        $reconAccount = $this->user()->accounts()->where('account_type_id', 10)->first();
        $euro         = TransactionCurrency::first();
        $foreign      = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $reconAccount->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $reconAccount);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $recon */
        $recon = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $count = $recon->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($recon, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $recon->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($recon->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairSameBadType(): void
    {
        // objects:
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue = $this->user()->accounts()->where('account_type_id', 5)->first();
        $euro    = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $expense->id,
            'source_name'           => null,
            'destination_id'        => $revenue->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($expense, $revenue);

        // factories return various stuff:
        $currencyFactory->shouldReceive('find')->andReturn($euro, null)->atLeast()->once();

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertEquals('At least one of the accounts must be an asset account (Expense account, Revenue account).', $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();
        $this->assertEquals($count, $newCount);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairSameType(): void
    {
        // objects:
        $asset     = $this->user()->accounts()->where('account_type_id', 3)->first();
        $alsoAsset = $this->user()->accounts()->where('account_type_id', 3)->first();
        $euro      = TransactionCurrency::first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $alsoAsset->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $alsoAsset);

        // factories return various stuff:
        $currencyFactory->shouldReceive('find')->andReturn($euro, null)->atLeast()->once();

        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $count      = $withdrawal->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->createPair($withdrawal, $data);
        } catch (FireflyException $e) {
            $this->assertEquals('Source and destination account cannot be both of the type "Asset account"', $e->getMessage());
        }

        $newCount = $withdrawal->transactions()->count();
        $this->assertEquals($count, $newCount);
    }

    /**
     * Create reconciliation using minimal (bad) data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testCreatePairTransfer(): void
    {
        // objects:
        $asset    = $this->user()->accounts()->where('account_type_id', 3)->first();
        $opposing = $this->user()->accounts()->where('id', '!=', $asset->id)->where('account_type_id', 3)->first();
        $euro     = TransactionCurrency::first();
        $foreign  = TransactionCurrency::where('id', '!=', $euro->id)->first();

        // mocked classes
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $budgetFactory   = $this->mock(BudgetFactory::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $data = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => null,
            'source_id'             => $asset->id,
            'source_name'           => null,
            'destination_id'        => $opposing->id,
            'destination_name'      => null,
            'amount'                => '10',
            'reconciled'            => false,
            'identifier'            => 0,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
        ];

        // mock:
        $accountRepos->shouldReceive('setUser');
        $budgetFactory->shouldReceive('setUser');
        $categoryFactory->shouldReceive('setUser');
        // first search action is for the asset account, second is for expense account.
        $accountRepos->shouldReceive('findNull')->andReturn($asset, $opposing);

        // factories return various stuff:
        $budgetFactory->shouldReceive('find')->andReturn(null);
        $categoryFactory->shouldReceive('findOrCreate')->andReturn(null);
        $currencyFactory->shouldReceive('find')->andReturn($euro, null);

        /** @var TransactionJournal $transfer */
        $transfer = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();
        $count    = $transfer->transactions()->count();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        try {
            $collection = $factory->createPair($transfer, $data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $newCount = $transfer->transactions()->count();

        $this->assertCount(2, $collection);
        $this->assertEquals($count + 2, $newCount);
        // find stuff in transaction #1 (should suffice):
        /** @var Transaction $first */
        $first = $collection->first();
        $this->assertEquals($transfer->id, $first->transaction_journal_id);
        $this->assertEquals(-10, $first->amount);
        $this->assertEquals(false, $first->reconciled);
        $this->assertEquals(0, $first->identifier);
        $this->assertEquals($euro->id, $first->transaction_currency_id);
        $this->assertNull($first->foreign_amount);
        $this->assertNull($first->foreign_currency_id);
    }

}
