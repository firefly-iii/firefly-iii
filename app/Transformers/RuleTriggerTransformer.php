<?php
/**
 * RuleTriggerTransformer.php
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


use FireflyIII\Models\RuleTrigger;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RuleTriggerTransformer
 */
class RuleTriggerTransformer extends TransformerAbstract
{
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
     * Transform the rule trigger.
     *
     * @param RuleTrigger $ruleTrigger
     *
     * @return array
     */
    public function transform(RuleTrigger $ruleTrigger): array
    {
        $data = [
            'id'              => (int)$ruleTrigger->id,
            'created_at'      => $ruleTrigger->created_at->toAtomString(),
            'updated_at'      => $ruleTrigger->updated_at->toAtomString(),
            'type'            => $ruleTrigger->trigger_type,
            'value'           => $ruleTrigger->trigger_value,
            'order'           => $ruleTrigger->order,
            'active'          => $ruleTrigger->active,
            'stop_processing' => $ruleTrigger->stop_processing,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/rule_triggers/' . $ruleTrigger->id,
                ],
            ],
        ];

        return $data;
    }
}
