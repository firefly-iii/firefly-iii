<?php
/**
 * TagFactoryTest.php
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


use FireflyIII\Factory\TagFactory;
use Log;
use Tests\TestCase;

/**
 * Class TagFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TagFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TagFactory
     */
    public function testFindOrCreateExisting(): void
    {
        $tag = $this->user()->tags()->first();
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($this->user());

        $result = $factory->findOrCreate($tag->tag);
        $this->assertEquals($tag->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TagFactory
     */
    public function testFindOrCreateNew(): void
    {
        $tag = sprintf('Some new tag %d', $this->randomInt());
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($this->user());

        $result = $factory->findOrCreate($tag);
        $this->assertEquals($tag, $result->tag);
    }

}
