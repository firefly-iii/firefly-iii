<?php
/**
 * RuleGroupTransformerTest.php
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


use FireflyIII\Models\RuleGroup;
use FireflyIII\Transformers\RuleGroupTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class RuleGroupTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RuleGroupTransformerTest extends TestCase
{
    /**
     * Test basic tag transformer
     *
     * @covers \FireflyIII\Transformers\RuleGroupTransformer
     */
    public function testBasic(): void
    {
        /** @var RuleGroup $ruleGroup */
        $ruleGroup = RuleGroup::first();


        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($ruleGroup);
        $this->assertEquals($ruleGroup->title, $result['title']);
    }

}
