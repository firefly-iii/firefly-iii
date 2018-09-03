<?php
/**
 * TransactionJournalMetaFactoryTest.php
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

use Carbon\Carbon;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use Log;
use Tests\TestCase;

/**
 * Class TransactionJournalMetaFactoryTest
 */
class TransactionJournalMetaFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateBasic(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $journal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => 'bye!',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $journal->transactionJournalMeta()->count());
        $this->assertEquals($set['data'], $result->data);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateDate(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $journal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => new Carbon('2012-01-01'),
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $journal->transactionJournalMeta()->count());
        $this->assertEquals($set['data']->toW3cString(), $result->data);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateDeleteExisting(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();
        $meta    = TransactionJournalMeta::create(
            [
                'transaction_journal_id' => $journal->id,
                'name'                   => 'hello',
                'data'                   => 'bye!',
            ]
        );
        $count   = $journal->transactionJournalMeta()->count();

        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => null,
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);

        $this->assertEquals($count - 1, $journal->transactionJournalMeta()->count());
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateEmpty(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $journal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => '',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(0, $journal->transactionJournalMeta()->count());
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateExistingEmpty(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $journal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => 'SomeData',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $journal->transactionJournalMeta()->count());
        $this->assertNotNull($result);

        // overrule with empty entry:
        $set = [
            'journal' => $journal,
            'name'    => 'hello',
            'data'    => '',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(0, $journal->transactionJournalMeta()->count());
        $this->assertNull($result);

    }


}
