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


use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use Mockery;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class TransactionTransformerTest
 */
class TransactionTransformerTest extends TestCase
{

    /**
     * Test basic transaction transformer.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer
     */
    public function testBasic(): void
    {
        $repository  = $this->mock(JournalRepositoryInterface::class);
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getNoteText')->once()->andReturn('Notes');

        // all meta fields:
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-cc'])->andReturn('a')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-op'])->andReturn('b')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-ud'])->andReturn('c')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-db'])->andReturn('d')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-country'])->andReturn('e')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ep'])->andReturn('f')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ci'])->andReturn('g')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-batch-id'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'internal_reference'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'bunq_payment_id'])->andReturn('12345')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'importHashV2'])->andReturn('abcdef')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'recurrence_id'])->andReturn('5')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'external_id'])->andReturn('1')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'original-source'])->andReturn('test')->atLeast()->once();

        // all meta dates.
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'interest_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'book_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'process_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'due_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'payment_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'invoice_date'])->andReturn('2018-01-01')->atLeast()->once();

        // get tags
        $repository->shouldReceive('getTags')->once()->andReturn(['a', 'b']);

        // create fake transaction object:
        $transaction = new Transaction;
        $journal     = TransactionJournal::find(1);

        // fill transaction with details
        $transaction->transactionJournal          = $journal;
        $transaction->created_at                  = new Carbon;
        $transaction->updated_at                  = new Carbon;
        $transaction->description                 = '';
        $transaction->transaction_description     = '';
        $transaction->date                        = new Carbon;
        $transaction->identifier                  = 0;
        $transaction->journal_id                  = 1;
        $transaction->reconciled                  = false;
        $transaction->transaction_amount          = '123.456';
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_code   = 'EUR';
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_dp     = 2;
        $transaction->bill_id                     = 1;
        $transaction->bill_name                   = 'Bill';
        $transaction->transaction_type_type       = 'Withdrawal';
        $transaction->transaction_budget_id       = 1;
        $transaction->transaction_budget_name     = 'X';
        $transaction->transaction_category_id     = 2;
        $transaction->transaction_category_name   = 'xab';

        // account info (for a withdrawal):
        $transaction->account_id            = 1;
        $transaction->account_name          = 'Some source';
        $transaction->account_iban          = 'IBAN';
        $transaction->account_type          = 'Asset account';
        $transaction->opposing_account_id   = 3;
        $transaction->opposing_account_name = 'Some destination';
        $transaction->opposing_account_iban = 'IBAN2';
        $transaction->opposing_account_type = 'Expense';


        // next test: foreign currency info.


        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($transaction);
        $this->assertEquals('Some source', $result['source_name']);

    }

    /**
     * Test deposit. Budget should be null, despite the link.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer
     */
    public function testDeposit(): void
    {
        $repository  = $this->mock(JournalRepositoryInterface::class);
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getNoteText')->once()->andReturn('Notes');

        // all meta fields:
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-cc'])->andReturn('a')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-op'])->andReturn('b')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-ud'])->andReturn('c')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-db'])->andReturn('d')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-country'])->andReturn('e')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ep'])->andReturn('f')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ci'])->andReturn('g')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-batch-id'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'internal_reference'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'bunq_payment_id'])->andReturn('12345')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'importHashV2'])->andReturn('abcdef')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'recurrence_id'])->andReturn('5')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'external_id'])->andReturn('1')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'original-source'])->andReturn('test')->atLeast()->once();

        // all meta dates.
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'interest_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'book_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'process_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'due_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'payment_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'invoice_date'])->andReturn('2018-01-01')->atLeast()->once();

        // get tags
        $repository->shouldReceive('getTags')->once()->andReturn(['a', 'b']);

        // create fake transaction object:
        $transaction = new Transaction;
        $journal     = TransactionJournal::find(1);

        // fill transaction with details
        $transaction->transactionJournal          = $journal;
        $transaction->created_at                  = new Carbon;
        $transaction->updated_at                  = new Carbon;
        $transaction->description                 = 'Some description';
        $transaction->transaction_description     = 'Some expanded description';
        $transaction->date                        = new Carbon;
        $transaction->identifier                  = 0;
        $transaction->journal_id                  = 1;
        $transaction->reconciled                  = false;
        $transaction->transaction_amount          = '123.456';
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_code   = 'EUR';
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_dp     = 2;
        $transaction->bill_id                     = 1;
        $transaction->bill_name                   = 'Bill';
        $transaction->transaction_type_type       = 'Deposit';
        $transaction->transaction_budget_id       = 1;
        $transaction->transaction_budget_name     = 'X';
        $transaction->transaction_category_id     = 2;
        $transaction->transaction_category_name   = 'xab';

        // foreign amount info:
        $transaction->transaction_foreign_amount = '456.789';
        $transaction->foreign_currency_dp        = 2;
        $transaction->foreign_currency_code      = 'USD';
        $transaction->foreign_currency_symbol    = 'x';

        // account info (for a withdrawal):
        $transaction->account_id            = 1;
        $transaction->account_name          = 'Some source';
        $transaction->account_iban          = 'IBAN';
        $transaction->account_type          = 'Asset account';
        $transaction->opposing_account_id   = 3;
        $transaction->opposing_account_name = 'Some destination';
        $transaction->opposing_account_iban = 'IBAN2';
        $transaction->opposing_account_type = 'Expense';


        // next test: foreign currency info.


        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($transaction);
        $this->assertEquals('Some destination', $result['source_name']);
        $this->assertEquals(456.79, $result['foreign_amount']);
        $this->assertEquals('Some expanded description', $result['transaction_description']);
        $this->assertEquals('Some description', $result['journal_description']);
        $this->assertEquals('Some expanded description (Some description)', $result['description']);
        $this->assertNull($result['budget_id']);
        $this->assertNull($result['budget_name']);
    }

    /**
     * Test transformer with foreign amount info.
     *
     * @covers \FireflyIII\Transformers\TransactionTransformer
     */
    public function testForeign(): void
    {
        $repository  = $this->mock(JournalRepositoryInterface::class);
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getNoteText')->once()->andReturn('Notes');

        // all meta fields:
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-cc'])->andReturn('a')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-op'])->andReturn('b')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ct-ud'])->andReturn('c')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-db'])->andReturn('d')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-country'])->andReturn('e')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ep'])->andReturn('f')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-ci'])->andReturn('g')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'sepa-batch-id'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'internal_reference'])->andReturn('h')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'bunq_payment_id'])->andReturn('12345')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'importHashV2'])->andReturn('abcdef')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'recurrence_id'])->andReturn('5')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'external_id'])->andReturn('1')->atLeast()->once();
        $repository->shouldReceive('getMetaField')->withArgs([Mockery::any(), 'original-source'])->andReturn('test')->atLeast()->once();

        // all meta dates.
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'interest_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'book_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'process_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'due_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'payment_date'])->andReturn('2018-01-01')->atLeast()->once();
        $repository->shouldReceive('getMetaDateString')->withArgs([Mockery::any(), 'invoice_date'])->andReturn('2018-01-01')->atLeast()->once();

        // get tags
        $repository->shouldReceive('getTags')->once()->andReturn(['a', 'b']);

        // create fake transaction object:
        $transaction = new Transaction;
        $journal     = TransactionJournal::find(1);

        // fill transaction with details
        $transaction->transactionJournal          = $journal;
        $transaction->created_at                  = new Carbon;
        $transaction->updated_at                  = new Carbon;
        $transaction->description                 = 'Some description';
        $transaction->transaction_description     = 'Some expanded description';
        $transaction->date                        = new Carbon;
        $transaction->identifier                  = 0;
        $transaction->journal_id                  = 1;
        $transaction->reconciled                  = false;
        $transaction->transaction_amount          = '123.456';
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_code   = 'EUR';
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_dp     = 2;
        $transaction->bill_id                     = 1;
        $transaction->bill_name                   = 'Bill';
        $transaction->transaction_type_type       = 'Withdrawal';
        $transaction->transaction_budget_id       = 1;
        $transaction->transaction_budget_name     = 'X';
        $transaction->transaction_category_id     = 2;
        $transaction->transaction_category_name   = 'xab';

        // foreign amount info:
        $transaction->transaction_foreign_amount = '456.789';
        $transaction->foreign_currency_dp        = 2;
        $transaction->foreign_currency_code      = 'USD';
        $transaction->foreign_currency_symbol    = 'x';

        // account info (for a withdrawal):
        $transaction->account_id            = 1;
        $transaction->account_name          = 'Some source';
        $transaction->account_iban          = 'IBAN';
        $transaction->account_type          = 'Asset account';
        $transaction->opposing_account_id   = 3;
        $transaction->opposing_account_name = 'Some destination';
        $transaction->opposing_account_iban = 'IBAN2';
        $transaction->opposing_account_type = 'Expense';


        // next test: foreign currency info.


        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($transaction);
        $this->assertEquals('Some source', $result['source_name']);
        $this->assertEquals(456.79, $result['foreign_amount']);
        $this->assertEquals('Some expanded description', $result['transaction_description']);
        $this->assertEquals('Some description', $result['journal_description']);
        $this->assertEquals('Some expanded description (Some description)', $result['description']);
    }


}
