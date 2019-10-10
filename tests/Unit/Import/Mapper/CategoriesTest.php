<?php
/**
 * CategoriesTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\Categories;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class CategoriesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoriesTest extends TestCase
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
     * @covers \FireflyIII\Import\Mapper\Categories
     */
    public function testGetMapBasic(): void
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
            0  => (string)trans('import.map_do_not_map'),
            17 => 'Else',
            9  => 'Something',

        ];
        $this->assertEquals($result, $mapping);
    }

}
