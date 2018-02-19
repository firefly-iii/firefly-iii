<?php
/**
 * TransactionTransformerTest.php
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

namespace Tests\Unit\Transformers;


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class TransactionTransformerTest
 */
class TransactionTransformerTest extends TestCase
{
    /**
     * Basic journal (withdrawal)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testBasic()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);
    }

    /**
     * Basic journal (withdrawal)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testDeposit()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new revenue account:
        $revenue = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 5, // revenue account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create deposit
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 2, // deposit
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $revenue->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Deposit', $result['type']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($revenue->name, $result['source_name']);
        $this->assertEquals($revenue->iban, $result['source_iban']);
        $this->assertEquals($revenue->id, $result['source_id']);
        $this->assertEquals('Revenue account', $result['source_type']);

        // destination:
        $this->assertEquals($asset->name, $result['destination_name']);
        $this->assertEquals($asset->iban, $result['destination_iban']);
        $this->assertEquals($asset->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);

    }

    /**
     * Deposit cannot have a budget
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testDepositBudget()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new revenue account:
        $revenue = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 5, // revenue account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create deposit
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 2, // deposit
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $revenue->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // create budget:
        $budget = Budget::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Random budget #' . rand(1, 1000),
                'active'  => 1,
            ]
        );
        $journal->budgets()->save($budget);

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Deposit', $result['type']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($revenue->name, $result['source_name']);
        $this->assertEquals($revenue->iban, $result['source_iban']);
        $this->assertEquals($revenue->id, $result['source_id']);
        $this->assertEquals('Revenue account', $result['source_type']);

        // destination:
        $this->assertEquals($asset->name, $result['destination_name']);
        $this->assertEquals($asset->iban, $result['destination_iban']);
        $this->assertEquals($asset->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);
    }

    /**
     * Basic journal (withdrawal) with a foreign amount.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testForeignAmount()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,
             'foreign_amount'          => -100, 'foreign_currency_id' => 2,
            ]
        );
        Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,
             'foreign_amount'          => 100, 'foreign_currency_id' => 2,
            ]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);

        // foreign info:
        $currency = TransactionCurrency::find(2);
        $this->assertEquals(-100, $result['foreign_amount']);
        $this->assertEquals($currency->code, $result['foreign_currency_code']);
    }

    /**
     * Basic journal (withdrawal) with a budget
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testJournalBudget()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );

        // create budget:
        $budget = Budget::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Random budget #' . rand(1, 1000),
                'active'  => 1,
            ]
        );
        $journal->budgets()->save($budget);

        // basic transactions
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);

        // budget and category:
        $this->assertNotNull($result['budget_id']);
        $this->assertNotNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);

        // budget ID and name:
        $this->assertEquals($budget->id, $result['budget_id']);
        $this->assertEquals($budget->name, $result['budget_name']);
    }

    /**
     * Basic journal (withdrawal) with a category
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testJournalCategory()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );

        // create category:
        $category = Category::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Random category #' . rand(1, 1000),
                'active'  => 1,
            ]
        );
        $journal->categories()->save($category);

        // basic transactions
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNotNull($result['category_id']);
        $this->assertNotNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);

        // budget ID and name:
        $this->assertEquals($category->id, $result['category_id']);
        $this->assertEquals($category->name, $result['category_name']);
    }

    /**
     * Basic journal (opening balance)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testOpeningBalanceNeg()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new initial balance account:
        $initial = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 6, // initial balance account
                'name'            => 'Initial balance for ' . $asset->name,
                'virtual_balance' => 0,
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 4, // opening balance
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions (negative opening balance).
        Transaction::create(
            ['account_id'              => $initial->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 100, 'identifier' => 0,]
        );

        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -100, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Opening balance', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($initial->name, $result['destination_name']);
        $this->assertEquals($initial->iban, $result['destination_iban']);
        $this->assertEquals($initial->id, $result['destination_id']);
        $this->assertEquals('Initial balance account', $result['destination_type']);

    }

    /**
     * Basic journal (opening balance)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testOpeningBalancePos()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new initial balance account:
        $initial = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 6, // initial balance account
                'name'            => 'Initial balance for ' . $asset->name,
                'virtual_balance' => 0,
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 4, // opening balance
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions (positive opening balance).

        Transaction::create(
            ['account_id'              => $initial->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -100, 'identifier' => 0,]
        );

        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 100, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Opening balance', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($initial->name, $result['source_name']);
        $this->assertEquals($initial->iban, $result['source_iban']);
        $this->assertEquals($initial->id, $result['source_id']);
        $this->assertEquals('Initial balance account', $result['source_type']);

        // destination:
        $this->assertEquals($asset->name, $result['destination_name']);
        $this->assertEquals($asset->iban, $result['destination_iban']);
        $this->assertEquals($asset->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);
    }

    /**
     * Basic journal (reconciliation)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testReconciliationNeg()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new initial balance account:
        $recon = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 10, // Reconciliation account
                'name'            => 'Reconciliation for something',
                'virtual_balance' => 0,
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 5, // reconciliation
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions (negative reconciliation).

        Transaction::create(
            ['account_id'              => $recon->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 100, 'identifier' => 0,]
        );

        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -100, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Reconciliation', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($recon->name, $result['destination_name']);
        $this->assertEquals($recon->iban, $result['destination_iban']);
        $this->assertEquals($recon->id, $result['destination_id']);
        $this->assertEquals('Reconciliation account', $result['destination_type']);
    }

    /**
     * Basic journal (reconciliation)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testReconciliationPos()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new initial balance account:
        $recon = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 10, // Reconciliation account
                'name'            => 'Reconciliation for something',
                'virtual_balance' => 0,
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 5, // reconciliation
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions (positive reconciliation).

        Transaction::create(
            ['account_id'              => $recon->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -100, 'identifier' => 0,]
        );

        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 100, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Reconciliation', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($recon->name, $result['source_name']);
        $this->assertEquals($recon->iban, $result['source_iban']);
        $this->assertEquals($recon->id, $result['source_id']);
        $this->assertEquals('Reconciliation account', $result['source_type']);

        // destination:
        $this->assertEquals($asset->name, $result['destination_name']);
        $this->assertEquals($asset->iban, $result['destination_iban']);
        $this->assertEquals($asset->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);
    }

    /**
     * Basic journal (withdrawal) with budget on the transactions.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testTransactionBudget()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );

        // create budget:
        $budget = Budget::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Random budget #' . rand(1, 1000),
                'active'  => 1,
            ]
        );


        // basic transactions
        $one = Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        $two = Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );
        $one->budgets()->save($budget);
        $two->budgets()->save($budget);

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);

        // budget and category:
        $this->assertNotNull($result['budget_id']);
        $this->assertNotNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);

        // budget ID and name:
        $this->assertEquals($budget->id, $result['budget_id']);
        $this->assertEquals($budget->name, $result['budget_name']);
    }

    /**
     * Basic journal (withdrawal) with a category on the transactions
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testTransactionCategory()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );

        // create category:
        $category = Category::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Random category #' . rand(1, 1000),
                'active'  => 1,
            ]
        );

        // basic transactions
        $one = Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        $two = Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );
        $one->categories()->save($category);
        $two->categories()->save($category);

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNotNull($result['category_id']);
        $this->assertNotNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);

        // budget ID and name:
        $this->assertEquals($category->id, $result['category_id']);
        $this->assertEquals($category->name, $result['category_name']);
    }

    /**
     * Basic journal (withdrawal) with a description for transactions.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testTransactionDescription()
    {
        // make new asset account:
        $asset = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new expense account:
        $expense = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $asset->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0, 'description' => 'Hello']
        );
        Transaction::create(
            ['account_id'              => $expense->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0, 'description' => 'Hello']
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Withdrawal', $result['type']);
        $this->assertEquals('Hello (' . $journal->description . ')', $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($asset->name, $result['source_name']);
        $this->assertEquals($asset->iban, $result['source_iban']);
        $this->assertEquals($asset->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($expense->name, $result['destination_name']);
        $this->assertEquals($expense->iban, $result['destination_iban']);
        $this->assertEquals($expense->id, $result['destination_id']);
        $this->assertEquals('Expense account', $result['destination_type']);
    }

    /**
     * Basic journal (transfer)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testTransferOne()
    {
        // make new asset account:
        $left = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new asset account:
        $right = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 3, // transfer
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions
        Transaction::create(
            ['account_id'              => $left->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $right->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Transfer', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($left->name, $result['source_name']);
        $this->assertEquals($left->iban, $result['source_iban']);
        $this->assertEquals($left->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($right->name, $result['destination_name']);
        $this->assertEquals($right->iban, $result['destination_iban']);
        $this->assertEquals($right->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);
    }

    /**
     * Basic journal (transfer)
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer::transform
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function testTransferTwo()
    {
        // make new asset account:
        $left = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // make new asset account:
        $right = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create withdrawal
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 3, // transfer
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Some journal',
                'date'                    => '2018-01-01',
                'completed'               => 1,
            ]
        );
        // basic transactions

        Transaction::create(
            ['account_id'              => $left->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => 45.67, 'identifier' => 0,]
        );

        Transaction::create(
            ['account_id'              => $right->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => -45.67, 'identifier' => 0,]
        );

        // use collector to get it:
        $transaction = $this->getTransaction($journal);
        $transformer = new TransactionTransformer(new ParameterBag);
        $result      = $transformer->transform($transaction);
        // basic fields:
        $this->assertEquals($journal->id, $result['journal_id']);
        $this->assertEquals('Transfer', $result['type']);
        $this->assertEquals($journal->description, $result['description']);

        // budget and category:
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
        $this->assertNull($result['category_id']);
        $this->assertNull($result['category_name']);

        // source:
        $this->assertEquals($right->name, $result['source_name']);
        $this->assertEquals($right->iban, $result['source_iban']);
        $this->assertEquals($right->id, $result['source_id']);
        $this->assertEquals('Asset account', $result['source_type']);

        // destination:
        $this->assertEquals($left->name, $result['destination_name']);
        $this->assertEquals($left->iban, $result['destination_iban']);
        $this->assertEquals($left->id, $result['destination_id']);
        $this->assertEquals('Asset account', $result['destination_type']);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction
     */
    protected function getTransaction(TransactionJournal $journal): Transaction
    {
        $collector = new JournalCollector;
        $collector->setUser($this->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setJournals(new Collection([$journal]));

        // add filter to remove transactions:
        $transactionType = $journal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }
        $journals = $collector->getJournals();

        return $journals->first();
    }


}