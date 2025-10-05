<?php

/**
 * RuleFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Rule;
use FireflyIII\Rules\IsValidActionExpression;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetRuleConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class RuleFormRequest.
 */
class RuleFormRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use GetRuleConfiguration;

    /**
     * Get all data for controller.
     */
    public function getRuleData(): array
    {
        return [
            'title'           => $this->convertString('title'),
            'rule_group_id'   => $this->convertInteger('rule_group_id'),
            'active'          => $this->boolean('active'),
            'trigger'         => $this->convertString('trigger'),
            'description'     => $this->stringWithNewlines('description'),
            'stop_processing' => $this->boolean('stop_processing'),
            'strict'          => $this->boolean('strict'),
            'triggers'        => $this->getRuleTriggerData(),
            'actions'         => $this->getRuleActionData(),
        ];
    }

    private function getRuleTriggerData(): array
    {
        $return      = [];
        $triggerData = $this->get('triggers');
        if (is_array($triggerData)) {
            foreach ($triggerData as $trigger) {
                $stopProcessing = $trigger['stop_processing'] ?? '0';
                $prohibited     = $trigger['prohibited'] ?? '0';
                $set            = [
                    'type'            => $trigger['type'] ?? 'invalid',
                    'value'           => $trigger['value'] ?? '',
                    'stop_processing' => 1 === (int) $stopProcessing,
                    'prohibited'      => 1 === (int) $prohibited,
                ];
                $set            = self::replaceAmountTrigger($set);
                $return[]       = $set;
            }
        }

        return $return;
    }

    public static function replaceAmountTrigger(array $array): array
    {
        // do some sneaky search and replace.
        $amountFields = [
            'amount_is',
            'amount',
            'amount_exactly',
            'amount_less',
            'amount_max',
            'amount_more',
            'amount_min',
            'foreign_amount_is',
            'foreign_amount',
            'foreign_amount_less',
            'foreign_amount_max',
            'foreign_amount_more',
            'foreign_amount_min',
        ];
        if (in_array($array['type'], $amountFields, true) && '0' === $array['value']) {
            $array['value'] = '0.00';
        }

        return $array;
    }

    private function getRuleActionData(): array
    {
        $return     = [];
        $actionData = $this->get('actions');
        if (is_array($actionData)) {
            foreach ($actionData as $action) {
                $stopProcessing = $action['stop_processing'] ?? '0';
                $return[]       = [
                    'type'            => $action['type'] ?? 'invalid',
                    'value'           => $action['value'] ?? '',
                    'stop_processing' => 1 === (int) $stopProcessing,
                ];
            }
        }

        return $return;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $validTriggers   = $this->getTriggers();
        $validActions    = array_keys(config('firefly.rule-actions'));

        // some actions require text (aka context):
        $contextActions  = implode(',', config('firefly.context-rule-actions'));

        // some triggers require text (aka context):
        $contextTriggers = implode(',', $this->getTriggersWithContext());

        // initial set of rules:
        $rules           = [
            'title'            => 'required|min:1|max:255|uniqueObjectForUser:rules,title',
            'description'      => 'min:1|max:32768|nullable',
            'stop_processing'  => 'boolean',
            'rule_group_id'    => 'required|belongsToUser:rule_groups',
            'trigger'          => 'required|in:store-journal,update-journal,manual-activation',
            'triggers.*.type'  => 'required|in:'.implode(',', $validTriggers),
            'triggers.*.value' => sprintf('required_if:triggers.*.type,%s|max:1024|min:1|ruleTriggerValue', $contextTriggers),
            'actions.*.type'   => 'required|in:'.implode(',', $validActions),
            'actions.*.value'  => [sprintf('required_if:actions.*.type,%s|min:0|max:1024', $contextActions), new IsValidActionExpression(), 'ruleActionValue'],
            'strict'           => 'in:0,1',
        ];

        /** @var null|Rule $rule */
        $rule            = $this->route()->parameter('rule');

        if (null !== $rule) {
            $rules['title'] = 'required|min:1|max:255|uniqueObjectForUser:rules,title,'.$rule->id;
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
