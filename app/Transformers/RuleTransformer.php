<?php
/**
 * RuleTransformer.php
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

namespace FireflyIII\Transformers;


use FireflyIII\Models\Rule;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RuleTransformer
 */
class RuleTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['rule_group', 'rule_triggers', 'rule_actions', 'user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['rule_group', 'rule_triggers', 'rule_actions'];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param Rule $rule
     *
     * @return FractalCollection
     */
    public function includeRuleActions(Rule $rule): FractalCollection
    {
        return $this->collection($rule->ruleActions, new RuleActionTransformer($this->parameters), 'rule_actions');
    }

    /**
     * Include the rule group.
     *
     * @param Rule $rule
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeRuleGroup(Rule $rule): Item
    {
        return $this->item($rule->ruleGroup, new RuleGroupTransformer($this->parameters), 'rule_groups');
    }

    /**
     * @param Rule $rule
     *
     * @return FractalCollection
     */
    public function includeRuleTriggers(Rule $rule): FractalCollection
    {
        return $this->collection($rule->ruleTriggers, new RuleTriggerTransformer($this->parameters), 'rule_triggers');
    }

    /**
     * Include the user.
     *
     * @param Rule $rule
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeUser(Rule $rule): Item
    {
        return $this->item($rule->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * Transform the rule.
     *
     * @param Rule $rule
     *
     * @return array
     */
    public function transform(Rule $rule): array
    {
        $data = [
            'id'              => (int)$rule->id,
            'updated_at'      => $rule->updated_at->toAtomString(),
            'created_at'      => $rule->created_at->toAtomString(),
            'title'           => $rule->title,
            'text'            => $rule->text,
            'order'           => (int)$rule->order,
            'active'          => $rule->active,
            'stop_processing' => $rule->stop_processing,
            'strict'          => $rule->strict,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/rules/' . $rule->id,
                ],
            ],
        ];

        return $data;
    }
}