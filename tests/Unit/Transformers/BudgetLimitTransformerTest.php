<?php
/**
 * BudgetLimitTransformerTest.php
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

namespace Tests\Unit\Transformers;


use FireflyIII\Models\BudgetLimit;
use FireflyIII\Transformers\BudgetLimitTransformer;
use Log;
use Tests\TestCase;

/**
 *
 * Class BudgetLimitTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetLimitTransformerTest extends TestCase
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
     * @covers \FireflyIII\Transformers\BudgetLimitTransformer
     */
    public function testBasic(): void
    {
        /** @var BudgetLimit $budgetLimit */
        $budgetLimit = BudgetLimit::first();

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $result      = $transformer->transform($budgetLimit);

        // compare results:
        $this->assertEquals($budgetLimit->id, $result['id']);
        $this->assertEquals($budgetLimit->start_date->format('Y-m-d'), $result['start']);
        $this->assertGreaterThanOrEqual(0, $result['amount']);
    }

}
