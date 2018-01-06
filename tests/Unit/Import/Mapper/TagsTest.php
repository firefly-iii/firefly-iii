<?php
/**
 * TagsTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\Tags;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class TagsTest
 */
class TagsTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Mapper\Tags::getMap()
     */
    public function testGetMapBasic()
    {
        $one        = new Tag;
        $one->id    = 12;
        $one->tag   = 'Something';
        $two        = new Tag;
        $two->id    = 14;
        $two->tag   = 'Else';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(TagRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn($collection)->once();

        $mapper  = new Tags();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0  => strval(trans('import.map_do_not_map')),
            14 => 'Else',
            12 => 'Something',
        ];
        $this->assertEquals($result, $mapping);
    }

}