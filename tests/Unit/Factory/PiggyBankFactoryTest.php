<?php
/**
 * PiggyBankFactoryTest.php
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


use FireflyIII\Factory\PiggyBankFactory;
use Log;
use Tests\TestCase;

/**
 * Class PiggyBankFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PiggyBankFactoryTest extends TestCase
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
     * Put in ID, return it.
     *
     * @covers \FireflyIII\Factory\PiggyBankFactory
     */
    public function testFindById(): void
    {
        $existing = $this->user()->piggyBanks()->first();
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user());

        $piggyBank = $factory->find($existing->id, null);
        $this->assertEquals($existing->id, $piggyBank->id);

    }

    /**
     * Put in name, return it.
     *
     * @covers \FireflyIII\Factory\PiggyBankFactory
     */
    public function testFindByName(): void
    {
        $existing = $this->user()->piggyBanks()->first();
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user());

        $piggyBank = $factory->find(null, $existing->name);
        $this->assertEquals($existing->id, $piggyBank->id);

    }

    /**
     * Put in NULL, will find NULL.
     *
     * @covers \FireflyIII\Factory\PiggyBankFactory
     */
    public function testFindNull(): void
    {
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user());

        $this->assertNull($factory->find(null, null));

    }

    /**
     * Put in unknown, get NULL
     *
     * @covers \FireflyIII\Factory\PiggyBankFactory
     */
    public function testFindUnknown(): void
    {
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->find(null, 'I dont exist.' . $this->randomInt()));
    }
}
