<?php
/**
 * CategoryFactoryTest.php
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

use FireflyIII\Factory\CategoryFactory;
use Log;
use Tests\TestCase;

/**
 * Class CategoryFactoryTest
 */
class CategoryFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\CategoryFactory
     */
    public function testFindOrCreateExistingID(): void
    {
        $existing = $this->user()->categories()->first();

        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user());
        $category = $factory->findOrCreate($existing->id, null);
        $this->assertEquals($existing->id, $category->id);
    }

    /**
     * @covers \FireflyIII\Factory\CategoryFactory
     */
    public function testFindOrCreateExistingName(): void
    {
        $existing = $this->user()->categories()->first();

        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user());
        $category = $factory->findOrCreate(null, $existing->name);
        $this->assertEquals($existing->id, $category->id);
    }

    /**
     * You can force a NULL result by presenting an invalid ID and no valid name.
     *
     * @covers \FireflyIII\Factory\CategoryFactory
     */
    public function testFindOrCreateInvalidID(): void
    {
        $existing = $this->user()->categories()->max('id');
        $existing += 4;

        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->findOrCreate($existing, ''));
    }

    /**
     * @covers \FireflyIII\Factory\CategoryFactory
     */
    public function testFindOrCreateNewName(): void
    {
        $name = sprintf('Some new category #%d', $this->randomInt());

        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user());
        $category = $factory->findOrCreate(null, $name);
        $this->assertEquals($name, $category->name);
    }

    /**
     * @covers \FireflyIII\Factory\CategoryFactory
     */
    public function testFindOrCreateNull(): void
    {
        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->findOrCreate(null, null));
    }

}
