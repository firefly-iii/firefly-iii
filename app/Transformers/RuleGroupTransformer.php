<?php
/**
 * RuleGroupTransformer.php
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

use FireflyIII\Models\RuleGroup;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RuleGroupTransformer
 */
class RuleGroupTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user'];

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
     * @param RuleGroup $ruleGroup
     *
     * @return FractalCollection
     */
    public function includeRules(RuleGroup $ruleGroup): FractalCollection
    {
        return $this->collection($ruleGroup->rules, new RuleTransformer($this->parameters), 'rules');
    }

    /**
     * Include the user.
     *
     * @param RuleGroup $ruleGroup
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeUser(RuleGroup $ruleGroup): Item
    {
        return $this->item($ruleGroup->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * Transform the rule group
     *
     * @param RuleGroup $ruleGroup
     *
     * @return array
     */
    public function transform(RuleGroup $ruleGroup): array
    {
        $data = [
            'id'         => (int)$ruleGroup->id,
            'updated_at' => $ruleGroup->updated_at->toAtomString(),
            'created_at' => $ruleGroup->created_at->toAtomString(),
            'title'      => $ruleGroup->title,
            'text'       => $ruleGroup->text,
            'order'      => $ruleGroup->order,
            'active'     => $ruleGroup->active,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/rule_groups/' . $ruleGroup->id,
                ],
            ],
        ];

        return $data;
    }


}


