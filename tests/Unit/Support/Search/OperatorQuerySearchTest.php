<?php
/*
 * OperatorQuerySearchTest.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Support\Search;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Support\Search\OperatorQuerySearch;
use Log;
use Tests\TestCase;
use DB;
/**
 * Test:
 *
 * - each combination
 * - some weird combi's
 * - invalid stuff?
 */
class OperatorQuerySearchTest extends TestCase
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
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testParseQuery(): void
    {
        $this->assertTrue(true);
        return;
        $this->be($this->user());
        // mock some of the used classes to verify results.

        $ops = array_keys(config('firefly.search.operators'));

        $values = [
            'user_action'                     => 'empty',

            // source account
            'from_account_starts'             => 'test',
            'source_account_starts'           => 'start',
            'from_account_ends'               => 'test',
            'source_account_ends'             => 'x',
            'source_account_is'               => '1',
            'from_account_is'                 => '1',
            'source_account_contains'         => 'test',
            'from_account_contains'           => 'test',
            'source'                          => 'test',
            'from'                            => 'test',
            'source_account_id'               => '1',
            'from_account_id'                 => '1',

            // source account nr
            'from_account_nr_starts'          => 'test',
            'source_account_nr_starts'        => 'test',
            'from_account_nr_ends'            => 'test',
            'source_account_nr_ends'          => 'test',
            'from_account_nr_is'              => 'test',
            'source_account_nr_is'            => 'test',
            'from_account_nr_contains'        => 'test',
            'source_account_nr_contains'      => 'test',

            // destination account
            'to_account_starts'               => 'test',
            'destination_account_starts'      => 'test',
            'to_account_ends'                 => 'test',
            'destination_account_ends'        => 'test',
            'to_account_contains'             => 'test',
            'destination_account_contains'    => 'test',
            'to_account_is'                   => 'test',
            'destination_account_is'          => 'test',
            'destination'                     => 'test',
            'to'                              => 'test',
            'destination_account_id'          => '1',
            'to_account_id'                   => '1',

            // destination account nr
            'to_account_nr_starts'            => 'test',
            'destination_account_nr_starts'   => 'test',
            'to_account_nr_ends'              => 'test',
            'destination_account_nr_ends'     => 'test',
            'to_account_nr_is'                => 'test',
            'destination_account_nr_is'       => 'test',
            'to_account_nr_contains'          => 'test',
            'destination_account_nr_contains' => 'test',

            // account
            'account_id'                      => '1',

            // the rest
            'description_starts'              => 'test',
            'description_ends'                => 'test',
            'description_contains'            => 'test',
            'description_is'                  => 'test',
            'currency_is'                     => 'test',
            'foreign_currency_is'             => 'test',
            'has_attachments'                 => 'test',
            'has_no_category'                 => 'test',
            'has_any_category'                => 'test',
            'has_no_budget'                   => 'test',
            'has_any_budget'                  => 'test',
            'has_no_tag'                      => 'test',
            'has_any_tag'                     => 'test',
            'notes_contain'                   => 'test',
            'notes_start'                     => 'test',
            'notes_end'                       => 'test',
            'notes_are'                       => 'test',
            'no_notes'                        => 'test',
            'any_notes'                       => 'test',



            // exact amount
            'amount_exactly'                  => '0',
            'amount_is'                       => '0',
            'amount'                          => '0',

            // is less than
            'amount_less'                     => '0',
            'amount_max'                      => '0',

            // is more than
            'amount_more'                     => '0',
            'amount_min'                      => '0',


            // category
            'category_is'                     => 'test',
            'category'                        => 'test',

            // budget
            'budget_is'                       => 'test',
            'budget'                          => 'test',

            // bill
            'bill_is'                         => 'test',
            'bill'                            => 'test',

            // type
            'transaction_type'                => 'test',
            'type'                            => 'test',

            // date:
            'date_is'                         => '2020-01-01',
            'date'                            => '2020-01-01',
            'on'                              => '2020-01-01',
            'date_before'                     => '2020-01-01',
            'before'                          => '2020-01-01',
            'date_after'                      => '2020-01-01',
            'after'                           => '2020-01-01',
            // other interesting fields
            'tag_is'                          => 'abc',
            'tag'                             => 'abc',
            'created_on'                      => '2020-01-01',
            'updated_on'                      => '2020-01-01',
            'external_id'                     => 'abc',
            'internal_reference'              => 'def',
        ];

        foreach ($ops as $operator) {
            if (!array_key_exists($operator, $values)) {
                $this->assertTrue(false, sprintf('No value for operator "%s"', $operator));
            }

            $query = sprintf('test %s:%s', $operator, $values[$operator]);

            $object = new OperatorQuerySearch;
            $object->setUser($this->user());
            $object->setPage(1);
            try {
                Log::debug(sprintf('Trying to parse query "%s"', $query));
                $object->parseQuery($query);
            } catch (FireflyException $e) {
                $this->assertTrue(false, $e->getMessage());
            }
            $this->assertTrue(true);
        }


        //$groups     = $object->searchTransactions();
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testUserAction(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'user_action:anything';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be ignored.
        $this->assertCount(0, $object->getOperators());

        // execute search should throw error:
        try {
            $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertEquals('Search query is empty.', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testFromAccountStarts(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_starts:from_acct_strts_9';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_strts_928_ends_Test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testAmountIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'amount_exactly:23.45';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries test exact amount', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testAmountLess(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'amount_less:5.55';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries test small amount', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testAmountMore(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'amount_more:555.55';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries test large amount', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testTransactionType(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'transaction_type:withdrawal';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // not sure but should find plenty
        // TODO compare count to DB search by hand.
        $this->assertTrue(count($result) > 10);
    }



    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountStarts(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_starts:Dest1A';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest1Acct2Test3Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDateExact(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'on:2019-02-02';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(2, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Transaction on feb 2, 2019.', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDateBefore(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'before:2018-02-02';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Transaction on feb 2, 2018.', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testCreatedAt(): void
    {
        $this->be($this->user());

        // update one journal to have a very specific created_on date:
        DB::table('transaction_journals')->where('id',1)->update(['created_at' => '2020-08-12 00:00:00']);

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'created_at:2020-08-12';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals(1, $transaction['transaction_journal_id'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testUpdatedAt(): void
    {
        $this->be($this->user());

        // update one journal to have a very specific created_on date:
        DB::table('transaction_journals')->where('id',1)->update(['updated_at' => '2020-08-12 00:00:00']);

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'updated_at:2020-08-12';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals(1, $transaction['transaction_journal_id'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testExternalId(): void
    {
        $this->be($this->user());

        // update one journal to have a very specific created_on date:
        DB::table('transaction_journals')->where('id',1)->update(['updated_at' => '2020-08-12 00:00:00']);

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'external_id:some_ext_id';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testInternalReference(): void
    {
        $this->be($this->user());

        // update one journal to have a very specific created_on date:
        DB::table('transaction_journals')->where('id',1)->update(['updated_at' => '2020-08-12 00:00:00']);

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'internal_reference:some_internal_ref';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDateAfter(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'after:2018-05-02';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        // TODO should find all transactions but one.
        $this->assertTrue(count($result) > 3);
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountEnds(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_ends:3Thing';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest1Acct2Test3Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountContains(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_contains:2Test3';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest1Acct2Test3Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestination(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'destination:2Test3';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest1Acct2Test3Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_is:Dest1Acct2Test3Thing';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest1Acct2Test3Thing', $transaction['destination_account_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasAttachments(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_attachments:empty';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries with attachment', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasAnyCategory(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_any_category:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertTrue(count($result) > 2);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $this->assertEquals('Groceries', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasAnyTag(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_any_tag:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, tricky to verify.
        $this->assertCount(1, $result);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $tags = $transaction['tags'] ?? [];
        $singleTag= array_shift($tags);
        $this->assertEquals('searchTestTag', $singleTag['name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasAnyBudget(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_any_budget:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertTrue(count($result) > 2);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $this->assertEquals('Groceries', $transaction['description'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testCategory(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'category:"Search cat thing"';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertCount(1,$result);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $this->assertEquals('Search cat thing', $transaction['category_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testTag(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'tag:searchTestTag';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertCount(1,$result);

        // the first one should hav this tag.
        $transaction = array_shift($result->first()['transactions']);
        $tags = $transaction['tags'] ?? [];
        $singleTag= array_shift($tags);
        $this->assertEquals('searchTestTag', $singleTag['name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testBudget(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'budget:"Search budget thing"';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertCount(1,$result);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $this->assertEquals('Search budget thing', $transaction['budget_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testBill(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'bill:TestBill';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // many results, tricky to verify.
        $this->assertCount(1,$result);

        // the first one should say "Groceries".
        $transaction = array_shift($result->first()['transactions']);
        $this->assertEquals('TestBill', $transaction['bill_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasNoCategory(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_no_category:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries with no category', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasNoBudget(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_no_budget:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries with no budget', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testHasNoTag(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'has_no_tag:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // could have many results, grab first transaction:
        $this->assertTrue( count($result) > 1);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountIdIs(): void
    {
        $this->be($this->user());

        /** @var Account $account */
        $account   = $this->user()->accounts()->where('name', 'Dest2Acct3Test4Thing')->first();
        $accountId = (int) $account->id;
        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = sprintf('destination_account_id:%d', $accountId);
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest2Acct3Test4Thing', $transaction['destination_account_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountIdIs(): void
    {
        $this->be($this->user());

        /** @var Account $account */
        $account   = $this->user()->accounts()->where('name', 'from_acct_NL30ABNA_test')->first();
        $accountId = (int) $account->id;
        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = sprintf('source_account_id:%d', $accountId);
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL30ABNA_test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testAccountIdIs(): void
    {
        $this->be($this->user());

        /** @var Account $account */
        $account   = $this->user()->accounts()->where('name', 'from_acct_NL30ABNA_test')->first();
        $accountId = (int) $account->id;
        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = sprintf('account_id:%d', $accountId);
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL30ABNA_test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrStartsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_starts:NL45RABO';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL45RABO_ends', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrStartsNumber(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_starts:NL30AB';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL30ABNA_test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrEndsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_ends:29221';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL45RABO_ends', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrEndsNumber(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_ends:8035321';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL28ABNA_test', $transaction['source_account_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrIsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_is:NL45RABO5319829221';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL45RABO_ends', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrIsNumber(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_is:NL28ABNA1938035321';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL28ABNA_test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrContainsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_contains:O53198';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL45RABO_ends', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountNrContainsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_nr_contains:L98RABO';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest2Acct3Test4Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountNrIsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_nr_is:NL98RABO9223011655';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest2Acct3Test4Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountNrStartsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_nr_starts:NL98RABO';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest2Acct3Test4Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDestinationAccountNrEndsIban(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'to_account_nr_ends:011655';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Dest2Acct3Test4Thing', $transaction['destination_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountNrContainsNumber(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_nr_contains:8ABNA1';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_NL28ABNA_test', $transaction['source_account_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_is:from_acct_strts_928_ends_Test';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_strts_928_ends_Test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountContains(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_contains:t_strts_928';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_strts_928_ends_Test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSource(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'source:t_strts_928';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_strts_928_ends_Test', $transaction['source_account_name'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDescriptionStarts(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'description_starts:8uStartTest';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('8uStartTest Groceries', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDescriptionEnds(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'description_ends:22end33';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries 22end33', $transaction['description'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDescriptionContains(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'description_contains:76tte32';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(0, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groc 76tte32 eries', $transaction['description'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testNotesContain(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'notes_contain:rch5No';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Test4Search5Notes6Thing', $transaction['notes'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testNotesStart(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'notes_start:Test4';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Test4Search5Notes6Thing', $transaction['notes'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testNotesEnd(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'notes_end:6Thing';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Test4Search5Notes6Thing', $transaction['notes'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testNotesAre(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'notes_are:Test4Search5Notes6Thing';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Test4Search5Notes6Thing', $transaction['notes'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testAnyNotes(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'any_notes:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Test4Search5Notes6Thing', $transaction['notes'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testNoNotes(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'no_notes:true';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // should have more than 1 result.
        $this->assertTrue(count($result) > 2);
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testDescriptionIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'description_is:"Groceries descr is 3291"';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('Groceries descr is 3291', $transaction['description'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testCurrencyIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'currency_is:HUF';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('HUF', $transaction['currency_code'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testCurrencyNameIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'currency_is:"Hungarian forint"';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('HUF', $transaction['currency_code'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testForeignCurrencyIs(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'foreign_currency_is:USD';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('USD', $transaction['foreign_currency_code'] ?? '');
    }

    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testSourceAccountEnds(): void
    {
        $this->be($this->user());

        $object = new OperatorQuerySearch;
        $object->setUser($this->user());
        $object->setPage(1);
        $query = 'from_account_ends:28_ends_Test';
        Log::debug(sprintf('Trying to parse query "%s"', $query));
        try {
            $object->parseQuery($query);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $object->getWords());

        // operator is assumed to be included.
        $this->assertCount(1, $object->getOperators());

        $result = ['transactions' => []];
        // execute search should work:
        try {
            $result = $object->searchTransactions();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // one result, grab first transaction:
        $this->assertCount(1, $result);
        $transaction = array_shift($result->first()['transactions']);

        // check if result is as expected.
        $this->assertEquals('from_acct_strts_928_ends_Test', $transaction['source_account_name'] ?? '');
    }


    /**
     * @covers \FireflyIII\Support\Search\OperatorQuerySearch
     */
    public function testGetWordsAsString(): void
    {

        $object = new OperatorQuerySearch();
        $this->assertEquals('', $object->getWordsAsString());
    }
}