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


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Factory\TransactionTypeFactory;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
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
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateBasic(): void
    {
        // mock used classes:
        $type               = TransactionType::find(1);
        $euro               = TransactionCurrency::find(1);
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $typeFactory        = $this->mock(TransactionTypeFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);
        $piggyFactory       = $this->mock(PiggyBankFactory::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $currencyRepos      = $this->mock(CurrencyRepositoryInterface::class);

        // mock stuff:
        $typeFactory->shouldReceive('find')->andReturn($type);
        $currencyRepos->shouldReceive('find')->andReturn($euro);

        $metaFactory->shouldReceive('updateOrCreate');

        // mock factories:
        $transactionFactory->shouldReceive('setUser')->once();
        $billFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        $transactionFactory->shouldReceive('createPair')->once();
        $billFactory->shouldReceive('find')->andReturn(null);
        $piggyFactory->shouldReceive('find')->andReturn(null);
        $data = [
            'type'            => 'withdrawal',
            'user'            => $this->user()->id,
            'description'     => 'I are journal',
            'date'            => new Carbon('2018-01-01'),
            'bill_id'         => null,
            'bill_name'       => null,
            'piggy_bank_id'   => null,
            'piggy_bank_name' => null,
            'notes'           => 'Hello',
            'tags'            => [],
            'transactions'    => [[]],
        ];

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        try {
            $journal = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['description'], $journal->description);
        $this->assertEquals('2018-01-01', $journal->date->format('Y-m-d'));
        $this->assertEquals(1, $journal->notes()->count());

    }

    /**
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateBasicEmptyAmount(): void
    {
        // mock used classes:
        $type               = TransactionType::find(1);
        $euro               = TransactionCurrency::find(1);
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $typeFactory        = $this->mock(TransactionTypeFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);
        $piggyFactory       = $this->mock(PiggyBankFactory::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $currencyRepos      = $this->mock(CurrencyRepositoryInterface::class);

        // mock stuff:
        $typeFactory->shouldReceive('find')->andReturn($type);
        $currencyRepos->shouldReceive('find')->andReturn($euro);

        $metaFactory->shouldReceive('updateOrCreate');

        // mock factories:
        $transactionFactory->shouldReceive('setUser')->once();
        $billFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        $transactionFactory->shouldReceive('createPair')->once();
        $billFactory->shouldReceive('find')->andReturn(null);
        $piggyFactory->shouldReceive('find')->andReturn(null);
        $data = [
            'type'            => 'withdrawal',
            'user'            => $this->user()->id,
            'description'     => 'I are journal',
            'date'            => new Carbon('2018-01-01'),
            'bill_id'         => null,
            'bill_name'       => null,
            'piggy_bank_id'   => null,
            'piggy_bank_name' => null,
            'notes'           => 'Hello',
            'tags'            => [],
            'transactions'    => [
                [
                    'amount' => '',
                ]
            ],
        ];

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        try {
            $journal = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['description'], $journal->description);
        $this->assertEquals('2018-01-01', $journal->date->format('Y-m-d'));
        $this->assertEquals(1, $journal->notes()->count());

    }


    /**
     * Same but with added meta data
     *
     * @covers \FireflyIII\Factory\TransactionJournalFactory
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testCreateBasicMeta(): void
    {
        // mock used classes:
        $type               = TransactionType::find(1);
        $euro               = TransactionCurrency::find(1);
        $piggy              = $this->user()->piggyBanks()->first();
        $bill               = $this->user()->bills()->first();
        $tag                = $this->user()->tags()->first();
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);
        $typeFactory        = $this->mock(TransactionTypeFactory::class);
        $transactionFactory = $this->mock(TransactionFactory::class);
        $piggyFactory       = $this->mock(PiggyBankFactory::class);
        $eventFactory       = $this->mock(PiggyBankEventFactory::class);
        $currencyRepos      = $this->mock(CurrencyRepositoryInterface::class);

        // mock stuff:
        $typeFactory->shouldReceive('find')->andReturn($type);
        $currencyRepos->shouldReceive('find')->andReturn($euro);

        // mock factories:
        $transactionFactory->shouldReceive('setUser')->once();
        $billFactory->shouldReceive('setUser')->once();
        $piggyFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('setUser')->once();

        $transactionFactory->shouldReceive('createPair')->once();
        $billFactory->shouldReceive('find')->andReturn($bill);
        $piggyFactory->shouldReceive('find')->andReturn($piggy);
        $eventFactory->shouldReceive('create')->once();
        $tagFactory->shouldReceive('findOrCreate')->andReturn($tag);
        $metaFactory->shouldReceive('updateOrCreate');

        $data = [
            'type'            => 'withdrawal',
            'user'            => $this->user()->id,
            'description'     => 'I are journal',
            'date'            => new Carbon('2018-01-01'),
            'bill_id'         => $bill->id,
            'bill_name'       => null,
            'piggy_bank_id'   => $piggy->id,
            'piggy_bank_name' => null,
            'notes'           => '',
            'tags'            => ['a', 'b', 'c'],
            'transactions'    => [[]],
            'interest_date'   => '2018-01-01',
        ];

        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user());
        try {
            $journal = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['description'], $journal->description);
        $this->assertEquals('2018-01-01', $journal->date->format('Y-m-d'));
        $this->assertEquals(0, $journal->notes()->count());

    }

}
