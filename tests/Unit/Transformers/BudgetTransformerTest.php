<?php
/**
 * BudgetTransformerTest.php
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

namespace Tests\Unit\Transformers;

use FireflyIII\Models\Budget;
use FireflyIII\Transformers\BudgetTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;


/**
 * Class BudgetTransformerTest
 */
class BudgetTransformerTest extends TestCase
{
    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BudgetTransformer::transform
     */
    public function testBasic()
    {

        $budget      = Budget::create(
            [
                'user_id' => $this->user()->id,
                'name'    => 'Some budget ' . rand(1, 10000),
                'active'  => 1,
            ]
        );
        $transformer = new BudgetTransformer(new ParameterBag);
        $result      = $transformer->transform($budget);

        $this->assertEquals($budget->name, $result['name']);
        $this->assertTrue($result['active']);
    }
}