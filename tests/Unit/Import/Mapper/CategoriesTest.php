<?php
/**
 * CategoriesTest.php
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

use FireflyIII\Import\Mapper\Categories;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class CategoriesTest
 */
class CategoriesTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Mapper\Categories::getMap()
     */
    public function testGetMapBasic()
    {
        $one        = new Category;
        $one->id    = 9;
        $one->name  = 'Something';
        $two        = new Category;
        $two->id    = 17;
        $two->name  = 'Else';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('getCategories')->andReturn($collection)->once();

        $mapper  = new Categories();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0  => strval(trans('import.map_do_not_map')),
            17 => 'Else',
            9  => 'Something',

        ];
        $this->assertEquals($result, $mapping);
    }

}