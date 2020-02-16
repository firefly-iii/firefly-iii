<?php
/**
 * CategoryTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Transformers\CategoryTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\Support\TestDataTrait;
use Tests\TestCase;


/**
 * Class CategoryTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryTransformerTest extends TestCase
{
    use TestDataTrait;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\CategoryTransformer
     */
    public function testBasic(): void
    {
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $opsRepository->shouldReceive('setUser')->once();

        /** @var Category $category */
        $category    = Category::first();
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($category);

        $this->assertEquals($category->name, $result['name']);
        $this->assertEquals([], $result['spent']);
        $this->assertEquals([], $result['earned']);
    }

    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\CategoryTransformer
     */
    public function testWithDates(): void
    {
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $opsRepository->shouldReceive('setUser')->once();

        $parameters = new ParameterBag;
        $parameters->set('start', new Carbon('2018-01-01'));
        $parameters->set('end', new Carbon('2018-01-31'));

        $income  = $this->categorySumIncome();
        $expense = $this->categorySumExpenses();
        $opsRepository->shouldReceive('sumIncome')
                      ->atLeast()->once()->andReturn($income);

        $opsRepository->shouldReceive('sumExpenses')
                      ->atLeast()->once()->andReturn($expense);

        /** @var Category $category */
        $category    = Category::first();
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($parameters);
        $result = $transformer->transform($category);

        $this->assertEquals($category->name, $result['name']);
    }
}
