<?php
/**
 * RuleFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Rule;

/**
 * Class RuleFormRequest.
 */
class RuleFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Get all data for controller.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getRuleData(): array
    {
        $data = [
            'title'           => $this->string('title'),
            'rule_group_id'   => $this->integer('rule_group_id'),
            'active'          => $this->boolean('active'),
            'trigger'         => $this->string('trigger'),
            'description'     => $this->string('description'),
            'stop_processing' => $this->boolean('stop_processing'),
            'strict'          => $this->boolean('strict'),
            'rule_triggers'   => $this->getRuleTriggerData(),
            'rule_actions'    => $this->getRuleActionData(),
        ];

        return $data;
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $validActions  = array_keys(config('firefly.rule-actions'));

        // some actions require text:
        $contextActions = implode(',', config('firefly.rule-actions-text'));

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title';
        /** @var Rule $rule */
        $rule = $this->route()->parameter('rule');

        if (null !== $rule) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title,' . $rule->id;
        }
        $rules = [
            'title'                 => $titleRule,
            'description'           => 'between:1,5000|nullable',
            'stop_processing'       => 'boolean',
            'rule_group_id'         => 'required|belongsToUser:rule_groups',
            'trigger'               => 'required|in:store-journal,update-journal',
            'rule_triggers.*.name'  => 'required|in:' . implode(',', $validTriggers),
            'rule_triggers.*.value' => 'required|min:1|ruleTriggerValue',
            'rule-actions.*.name'   => 'required|in:' . implode(',', $validActions),
            'strict'                => 'in:0,1',
        ];
        // since Laravel does not support this stuff yet, here's a trick.
        for ($i = 0; $i < 10; ++$i) {
            $key         = sprintf('rule_actions.%d.value', $i);
            $rule        = sprintf('required-if:rule_actions.%d.name,%s|ruleActionValue', $i, $contextActions);
            $rules[$key] = $rule;
        }

        return $rules;
    }

    /**
     * @return array
     */
    private function getRuleActionData(): array
    {
        $return     = [];
        $actionData = $this->get('rule_actions');
        if (\is_array($actionData)) {
            foreach ($actionData as $action) {
                $stopProcessing = $action['stop_processing'] ?? '0';
                $return[]       = [
                    'name'            => $action['name'] ?? 'invalid',
                    'value'           => $action['value'] ?? '',
                    'stop_processing' => 1 === (int)$stopProcessing,
                ];
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    private function getRuleTriggerData(): array
    {
        $return      = [];
        $triggerData = $this->get('rule_triggers');
        if (\is_array($triggerData)) {
            foreach ($triggerData as $trigger) {
                $stopProcessing = $trigger['stop_processing'] ?? '0';
                $return[]       = [
                    'name'            => $trigger['name'] ?? 'invalid',
                    'value'           => $trigger['value'] ?? '',
                    'stop_processing' => 1 === (int)$stopProcessing,
                ];
            }
        }

        return $return;
    }
}
