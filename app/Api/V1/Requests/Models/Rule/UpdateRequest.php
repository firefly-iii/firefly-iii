<?php
/**
 * RuleUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Rule;

use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetRuleConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use function is_array;


/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ConvertsDataTypes, GetRuleConfiguration, ChecksLogin;

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $fields = [
            'title'           => ['title', 'string'],
            'description'     => ['description', 'nlString'],
            'rule_group_id'   => ['rule_group_id', 'integer'],
            'trigger'         => ['trigger', 'string'],
            'strict'          => ['strict', 'boolean'],
            'stop_processing' => ['stop_processing', 'boolean'],
            'active'          => ['active', 'boolean'],
        ];

        $return   = $this->getAllData($fields);
        $triggers = $this->getRuleTriggers();
        $actions  = $this->getRuleActions();
        if (null !== $triggers) {
            $return['triggers'] = $triggers;
        }
        if (null !== $actions) {
            $return['actions'] = $actions;
        }

        return $return;
    }

    /**
     * @return array|null
     */
    private function getRuleTriggers(): ?array
    {
        if (!$this->has('triggers')) {
            return null;
        }
        $triggers = $this->get('triggers');
        $return   = [];
        if (is_array($triggers)) {
            foreach ($triggers as $trigger) {
                $active         = array_key_exists('active', $trigger) ? $trigger['active'] : true;
                $stopProcessing = array_key_exists('stop_processing', $trigger) ? $trigger['stop_processing'] : false;
                $return[]       = [
                    'type'            => $trigger['type'],
                    'value'           => $trigger['value'],
                    'active'          => $active,
                    'stop_processing' => $stopProcessing,
                ];
            }
        }

        return $return;
    }

    /**
     * @return array|null
     */
    private function getRuleActions(): ?array
    {
        if (!$this->has('actions')) {
            return null;
        }
        $actions = $this->get('actions');
        $return  = [];
        if (is_array($actions)) {
            foreach ($actions as $action) {
                $return[] = [
                    'type'            => $action['type'],
                    'value'           => $action['value'],
                    'active'          => $this->convertBoolean((string)($action['active'] ?? 'false')),
                    'stop_processing' => $this->convertBoolean((string)($action['stop_processing'] ?? 'false')),
                ];
            }
        }

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $validTriggers = $this->getTriggers();
        $validActions  = array_keys(config('firefly.rule-actions'));
        $rule          = $this->route()->parameter('rule');

        // some triggers and actions require text:
        $contextTriggers = implode(',', $this->getTriggersWithContext());
        $contextActions  = implode(',', config('firefly.context-rule-actions'));

        return [
            'title'                      => sprintf('between:1,100|uniqueObjectForUser:rules,title,%d', $rule->id),
            'description'                => 'between:1,5000|nullable',
            'rule_group_id'              => 'belongsToUser:rule_groups',
            'rule_group_title'           => 'nullable|between:1,255|belongsToUser:rule_groups,title',
            'trigger'                    => 'in:store-journal,update-journal',
            'triggers.*.type'            => 'required|in:' . implode(',', $validTriggers),
            'triggers.*.value'           => 'required_if:actions.*.type,' . $contextTriggers . '|min:1|ruleTriggerValue',
            'triggers.*.stop_processing' => [new IsBoolean],
            'triggers.*.active'          => [new IsBoolean],
            'actions.*.type'             => 'required|in:' . implode(',', $validActions),
            'actions.*.value'            => 'required_if:actions.*.type,' . $contextActions . '|ruleActionValue',
            'actions.*.stop_processing'  => [new IsBoolean],
            'actions.*.active'           => [new IsBoolean],
            'strict'                     => [new IsBoolean],
            'stop_processing'            => [new IsBoolean],
            'active'                     => [new IsBoolean],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $this->atLeastOneTrigger($validator);
                $this->atLeastOneAction($validator);
            }
        );
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
     */
    protected function atLeastOneTrigger(Validator $validator): void
    {
        $data     = $validator->getData();
        $triggers = $data['triggers'] ?? null;
        // need at least one trigger
        if (is_array($triggers) && 0 === count($triggers)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_trigger'));
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
     */
    protected function atLeastOneAction(Validator $validator): void
    {
        $data    = $validator->getData();
        $actions = $data['actions'] ?? null;
        // need at least one action
        if (is_array($actions) && 0 === count($actions)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_action'));
        }
    }
}
