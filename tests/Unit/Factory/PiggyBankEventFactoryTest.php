<?php
/**
 * PiggyBankEventFactoryTest.php
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


use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class PiggyBankEventFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PiggyBankEventFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateAmountZero(): void
    {
        $transfer   = $this->getRandomTransfer();
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $repetition = PiggyBankRepetition::first();
        $repos      = $this->mock(PiggyBankRepositoryInterface::class);
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn($repetition);
        $repos->shouldReceive('getExactAmount')->andReturn('0');

        $this->assertNull($factory->create($transfer, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNoPiggy(): void
    {
        $this->mock(PiggyBankRepositoryInterface::class);
        $transfer = $this->getRandomTransfer();

        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        $this->assertNull($factory->create($transfer, null));
    }

    /**
     * Test for withdrawal where piggy has no repetition.
     *
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNoRep(): void
    {
        $transfer = $this->getRandomTransfer();
        $piggy    = $this->user()->piggyBanks()->first();
        $repos    = $this->mock(PiggyBankRepositoryInterface::class);
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn(null);
        $repos->shouldReceive('getExactAmount')->andReturn('0');

        Log::warning('The following error is part of a test.');
        $this->assertNull($factory->create($transfer, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNotTransfer(): void
    {
        $this->mock(PiggyBankRepositoryInterface::class);
        $deposit = $this->getRandomDeposit();

        $piggy = $this->user()->piggyBanks()->first();
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);
        Log::warning('The following error is part of a test.');
        $this->assertNull($factory->create($deposit, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateSuccess(): void
    {
        $transfer   = $this->getRandomTransfer();
        $piggy      = $this->user()->piggyBanks()->first();
        $repetition = PiggyBankRepetition::first();
        $event      = PiggyBankEvent::first();
        $repos      = $this->mock(PiggyBankRepositoryInterface::class);

        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn($repetition);
        $repos->shouldReceive('getExactAmount')->andReturn('5');
        $repos->shouldReceive('addAmountToRepetition')->once();
        $repos->shouldReceive('createEventWithJournal')->once()->andReturn($event);

        $result = $factory->create($transfer, $piggy);
        $this->assertNotnull($result);
        $this->assertEquals($result->id, $event->id);
    }

}
