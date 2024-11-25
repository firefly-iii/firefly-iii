<?php

/**
 * RuleTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;

/**
 * Class RuleTransformer
 */
class RuleTransformer extends AbstractTransformer
{
    private RuleRepositoryInterface $ruleRepository;

    /**
     * CurrencyTransformer constructor.
     */
    public function __construct()
    {
        $this->ruleRepository = app(RuleRepositoryInterface::class);
    }

    /**
     * Transform the rule.
     *
     * @throws FireflyException
     */
    public function transform(Rule $rule): array
    {
        $this->ruleRepository->setUser($rule->user);

        return [
            'id'               => (string)$rule->id,
            'created_at'       => $rule->created_at->toAtomString(),
            'updated_at'       => $rule->updated_at->toAtomString(),
            'rule_group_id'    => (string)$rule->rule_group_id,
            'rule_group_title' => (string)$rule->ruleGroup->title,
            'title'            => $rule->title,
            'description'      => $rule->description,
            'order'            => $rule->order,
            'active'           => $rule->active,
            'strict'           => $rule->strict,
            'stop_processing'  => $rule->stop_processing,
            'trigger'          => $this->getRuleTrigger($rule),
            'triggers'         => $this->triggers($rule),
            'actions'          => $this->actions($rule),
            'links'            => [
                [
                    'rel' => 'self',
                    'uri' => '/rules/'.$rule->id,
                ],
            ],
        ];
    }

    /**
     * @throws FireflyException
     */
    private function getRuleTrigger(Rule $rule): string
    {
        $moment   = null;
        $triggers = $this->ruleRepository->getRuleTriggers($rule);

        /** @var RuleTrigger $ruleTrigger */
        foreach ($triggers as $ruleTrigger) {
            if ('user_action' === $ruleTrigger->trigger_type) {
                $moment = $ruleTrigger->trigger_value;
            }
        }
        if (null === $moment) {
            throw new FireflyException(sprintf('Rule #%d has no valid trigger moment. Edit it in the Firefly III user interface to correct this.', $rule->id));
        }

        return $moment;
    }

    private function triggers(Rule $rule): array
    {
        $result   = [];
        $triggers = $this->ruleRepository->getRuleTriggers($rule);

        /** @var RuleTrigger $ruleTrigger */
        foreach ($triggers as $ruleTrigger) {
            if ('user_action' === $ruleTrigger->trigger_type) {
                continue;
            }
            $triggerType  = (string) $ruleTrigger->trigger_type;
            $triggerValue = (string)$ruleTrigger->trigger_value;
            $prohibited   = false;

            if (str_starts_with($triggerType, '-')) {
                $prohibited  = true;
                $triggerType = substr($triggerType, 1);
            }

            $needsContext = config(sprintf('search.operators.%s.needs_context', $triggerType), true);
            if (false === $needsContext) {
                $triggerValue = 'true';
            }

            $result[]     = [
                'id'              => (string)$ruleTrigger->id,
                'created_at'      => $ruleTrigger->created_at->toAtomString(),
                'updated_at'      => $ruleTrigger->updated_at->toAtomString(),
                'type'            => $triggerType,
                'value'           => $triggerValue,
                'prohibited'      => $prohibited,
                'order'           => $ruleTrigger->order,
                'active'          => $ruleTrigger->active,
                'stop_processing' => $ruleTrigger->stop_processing,
            ];
        }

        return $result;
    }

    private function actions(Rule $rule): array
    {
        $result  = [];
        $actions = $this->ruleRepository->getRuleActions($rule);

        /** @var RuleAction $ruleAction */
        foreach ($actions as $ruleAction) {
            $result[] = [
                'id'              => (string)$ruleAction->id,
                'created_at'      => $ruleAction->created_at->toAtomString(),
                'updated_at'      => $ruleAction->updated_at->toAtomString(),
                'type'            => $ruleAction->action_type,
                'value'           => $ruleAction->action_value,
                'order'           => $ruleAction->order,
                'active'          => $ruleAction->active,
                'stop_processing' => $ruleAction->stop_processing,
            ];
        }

        return $result;
    }
}
