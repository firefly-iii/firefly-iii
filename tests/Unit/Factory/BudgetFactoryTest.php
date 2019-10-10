<?php
/**
 * BudgetFactoryTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use FireflyIII\Factory\BudgetFactory;
use Log;
use Tests\TestCase;

/**
 * Class BudgetFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindById(): void
    {
        $existing = $this->user()->budgets()->first();
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $budget = $factory->find($existing->id, null);
        $this->assertEquals($existing->id, $budget->id);

    }

    /**
     * Put in name, return it.
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindByName(): void
    {
        $existing = $this->user()->budgets()->first();
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $budget = $factory->find(null, $existing->name);
        $this->assertEquals($existing->id, $budget->id);

    }

    /**
     * Put in NULL, will find NULL.
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindNull(): void
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $this->assertNull($factory->find(null, null));

    }

    /**
     * Put in unknown, get NULL
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindUnknown(): void
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->find(null, sprintf('I dont exist %d', $this->randomInt())));
    }

}
