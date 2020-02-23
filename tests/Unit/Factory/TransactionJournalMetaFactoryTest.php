<?php
/**
 * TransactionJournalMetaFactoryTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

declare(strict_types=1);

namespace Tests\Unit\Factory;

use Carbon\Carbon;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\TransactionJournalMeta;
use Log;
use Tests\TestCase;

/**
 * Class TransactionJournalMetaFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionJournalMetaFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateBasic(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $withdrawal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => 'bye!',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $withdrawal->transactionJournalMeta()->count());
        $this->assertEquals($set['data'], $result->data);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateDate(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $withdrawal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => new Carbon('2012-01-01'),
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $withdrawal->transactionJournalMeta()->count());
        $this->assertEquals($set['data']->toW3cString(), $result->data);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateDeleteExisting(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        TransactionJournalMeta::create(
            [
                'transaction_journal_id' => $withdrawal->id,
                'name'                   => 'hello',
                'data'                   => 'bye!',
            ]
        );
        $count = $withdrawal->transactionJournalMeta()->count();

        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => null,
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);

        $this->assertEquals($count - 1, $withdrawal->transactionJournalMeta()->count());
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateEmpty(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $withdrawal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => '',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(0, $withdrawal->transactionJournalMeta()->count());
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalMetaFactory
     */
    public function testUpdateOrCreateExistingEmpty(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $withdrawal->transactionJournalMeta()->delete();
        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => 'SomeData',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(1, $withdrawal->transactionJournalMeta()->count());
        $this->assertNotNull($result);

        // overrule with empty entry:
        $set = [
            'journal' => $withdrawal,
            'name'    => 'hello',
            'data'    => '',
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $result  = $factory->updateOrCreate($set);

        $this->assertEquals(0, $withdrawal->transactionJournalMeta()->count());
        $this->assertNull($result);

    }


}
