<?php
/**
 * RuleTransformerTest.php
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


use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Transformers\RuleTransformer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class RuleTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RuleTransformerTest extends TestCase
{
    /**
     * Test basic tag transformer
     *
     * @covers \FireflyIII\Transformers\RuleTransformer
     */
    public function testBasic(): void
    {
        /** @var Rule $rule */
        $rule = Rule::first();

        $repository = $this->mock(RuleRepositoryInterface::class);
        /** @var RuleTrigger $ruleTrigger */
        $ruleTrigger = RuleTrigger::where('trigger_type', '!=', 'user_action')->first();

        /** @var RuleTrigger $ruleTrigger */
        $moment = RuleTrigger::where('trigger_type', '=', 'user_action')->first();

        /** @var RuleAction $ruleAction */
        $ruleAction = RuleAction::first();
        // mock stuff
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getRuleActions')->atLeast()->once()->andReturn(new Collection([$ruleAction]));
        $repository->shouldReceive('getRuleTriggers')->atLeast()->once()->andReturn(new Collection([$moment]), new Collection([$ruleTrigger]));

        $transformer = app(RuleTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($rule);
        $this->assertEquals($rule->title, $result['title']);

        $this->assertEquals($ruleTrigger->trigger_type, $result['triggers'][0]['type']);
        $this->assertEquals($ruleAction->action_type, $result['actions'][0]['type']);
    }

}
