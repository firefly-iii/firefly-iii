<?php
/**
 * RuleRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use Illuminate\Validation\Validator;


/**
 * Class RuleRequest
 */
class RuleRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            'title'            => $this->string('title'),
            'description'      => $this->string('description'),
            'rule_group_id'    => $this->integer('rule_group_id'),
            'rule_group_title' => $this->string('rule_group_title'),
            'trigger'          => $this->string('trigger'),
            'strict'           => $this->boolean('strict'),
            'stop-processing'  => $this->boolean('stop_processing'),
            'active'           => $this->boolean('active'),
            'rule-triggers'    => [],
            'rule-actions'     => [],
        ];

        foreach ($this->get('rule-triggers') as $trigger) {
            $data['rule-triggers'][] = [
                'name'            => $trigger['name'],
                'value'           => $trigger['value'],
                'stop-processing' => 1 === (int)($trigger['stop-processing'] ?? 0),
            ];
        }
        foreach ($this->get('rule-actions') as $action) {
            $data['rule-actions'][] = [
                'name'            => $action['name'],
                'value'           => $action['value'],
                'stop-processing' => 1 === (int)($action['stop-processing'] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $validActions  = array_keys(config('firefly.rule-actions'));

        // some actions require text:
        $contextActions = implode(',', config('firefly.rule-actions-text'));

        $rules = [
            'title'                           => 'required|between:1,100|uniqueObjectForUser:rules,title',
            'description'                     => 'between:1,5000|nullable',
            'rule_group_id'                   => 'required|belongsToUser:rule_groups|required_without:rule_group_title',
            'rule_group_title'                => 'nullable|between:1,255|required_without:rule_group_id|belongsToUser:rule_groups,title',
            'trigger'                         => 'required|in:store-journal,update-journal',
            'rule-triggers.*.name'            => 'required|in:' . implode(',', $validTriggers),
            'rule-triggers.*.stop-processing' => 'boolean',
            'rule-triggers.*.value'           => 'required|min:1|ruleTriggerValue', //
            'rule-actions.*.name'             => 'required|in:' . implode(',', $validActions),
            'rule-actions.*.value'            => 'required_if:rule-action.*.type,' . $contextActions . '|ruleActionValue',
            'rule-actions.*.stop-processing'  => 'boolean',
            'strict'                          => 'required|boolean',
            'stop_processing'                 => 'required|boolean',
            'active'                          => 'required|boolean',
        ];

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
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
    protected function atLeastOneAction(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['rule-actions'] ?? [];
        // need at least one transaction
        if (0 === \count($repetitions)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_action'));
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
     */
    protected function atLeastOneTrigger(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['rule-triggers'] ?? [];
        // need at least one transaction
        if (0 === \count($repetitions)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_trigger'));
        }
    }
}