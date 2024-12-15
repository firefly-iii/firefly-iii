<?php

/**
 * RuleStoreRequest.php
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
use FireflyIII\Rules\IsValidActionExpression;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetRuleConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use GetRuleConfiguration;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $fields           = [
            'title'            => ['title', 'convertString'],
            'description'      => ['description', 'convertString'],
            'rule_group_id'    => ['rule_group_id', 'convertInteger'],
            'order'            => ['order', 'convertInteger'],
            'rule_group_title' => ['rule_group_title', 'convertString'],
            'trigger'          => ['trigger', 'convertString'],
            'strict'           => ['strict', 'boolean'],
            'stop_processing'  => ['stop_processing', 'boolean'],
            'active'           => ['active', 'boolean'],
        ];
        $data             = $this->getAllData($fields);
        $data['triggers'] = $this->getRuleTriggers();
        $data['actions']  = $this->getRuleActions();

        return $data;
    }

    private function getRuleTriggers(): array
    {
        $triggers = $this->get('triggers');
        $return   = [];
        if (is_array($triggers)) {
            foreach ($triggers as $trigger) {
                $return[] = [
                    'type'            => $trigger['type'] ?? '',
                    'value'           => $trigger['value'] ?? null,
                    'prohibited'      => $this->convertBoolean((string)($trigger['prohibited'] ?? 'false')),
                    'active'          => $this->convertBoolean((string)($trigger['active'] ?? 'true')),
                    'stop_processing' => $this->convertBoolean((string)($trigger['stop_processing'] ?? 'false')),
                ];
            }
        }

        return $return;
    }

    private function getRuleActions(): array
    {
        $actions = $this->get('actions');
        $return  = [];
        if (is_array($actions)) {
            foreach ($actions as $action) {
                $return[] = [
                    'type'            => $action['type'],
                    'value'           => $action['value'],
                    'active'          => $this->convertBoolean((string)($action['active'] ?? 'true')),
                    'stop_processing' => $this->convertBoolean((string)($action['stop_processing'] ?? 'false')),
                ];
            }
        }

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        $validTriggers   = $this->getTriggers();
        $validActions    = array_keys(config('firefly.rule-actions'));

        // some triggers and actions require text:
        $contextTriggers = implode(',', $this->getTriggersWithContext());
        $contextActions  = implode(',', config('firefly.context-rule-actions'));

        return [
            'title'                      => 'required|min:1|max:100|uniqueObjectForUser:rules,title',
            'description'                => 'min:1|max:32768|nullable',
            'rule_group_id'              => 'belongsToUser:rule_groups|required_without:rule_group_title',
            'rule_group_title'           => 'nullable|min:1|max:255|required_without:rule_group_id|belongsToUser:rule_groups,title',
            'trigger'                    => 'required|in:store-journal,update-journal,manual-activation',
            'triggers.*.type'            => 'required|in:'.implode(',', $validTriggers),
            'triggers.*.value'           => 'required_if:actions.*.type,'.$contextTriggers.'|min:1|ruleTriggerValue|max:1024',
            'triggers.*.stop_processing' => [new IsBoolean()],
            'triggers.*.active'          => [new IsBoolean()],
            'actions.*.type'             => 'required|in:'.implode(',', $validActions),
            'actions.*.value'            => [sprintf('required_if:actions.*.type,%s', $contextActions), new IsValidActionExpression(), 'ruleActionValue'],
            'actions.*.stop_processing'  => [new IsBoolean()],
            'actions.*.active'           => [new IsBoolean()],
            'strict'                     => [new IsBoolean()],
            'stop_processing'            => [new IsBoolean()],
            'active'                     => [new IsBoolean()],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $this->atLeastOneTrigger($validator);
                $this->atLeastOneAction($validator);
                $this->atLeastOneActiveTrigger($validator);
                $this->atLeastOneActiveAction($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }

    /**
     * Adds an error to the validator when there are no triggers in the array of data.
     */
    protected function atLeastOneTrigger(Validator $validator): void
    {
        $data     = $validator->getData();
        $triggers = $data['triggers'] ?? [];
        // need at least one trigger
        if (!is_countable($triggers) || 0 === count($triggers)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_trigger'));
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     */
    protected function atLeastOneAction(Validator $validator): void
    {
        $data    = $validator->getData();
        $actions = $data['actions'] ?? [];
        // need at least one trigger
        if (!is_countable($actions) || 0 === count($actions)) {
            $validator->errors()->add('title', (string)trans('validation.at_least_one_action'));
        }
    }

    /**
     * Adds an error to the validator when there are no ACTIVE triggers in the array of data.
     */
    protected function atLeastOneActiveTrigger(Validator $validator): void
    {
        $data          = $validator->getData();

        /** @var null|array|int|string $triggers */
        $triggers      = $data['triggers'] ?? [];
        // need at least one trigger
        if (!is_countable($triggers) || 0 === count($triggers)) {
            return;
        }
        $allInactive   = true;
        $inactiveIndex = 0;
        foreach ($triggers as $index => $trigger) {
            $active = array_key_exists('active', $trigger) ? $trigger['active'] : true; // assume true
            if (true === $active) {
                $allInactive = false;
            }
            if (false === $active) {
                $inactiveIndex = $index;
            }
        }
        if (true === $allInactive) {
            $validator->errors()->add(sprintf('triggers.%d.active', $inactiveIndex), (string)trans('validation.at_least_one_active_trigger'));
        }
    }

    /**
     * Adds an error to the validator when there are no ACTIVE actions in the array of data.
     */
    protected function atLeastOneActiveAction(Validator $validator): void
    {
        $data          = $validator->getData();

        /** @var null|array|int|string $actions */
        $actions       = $data['actions'] ?? [];
        // need at least one trigger
        if (!is_countable($actions) || 0 === count($actions)) {
            return;
        }
        $allInactive   = true;
        $inactiveIndex = 0;
        foreach ($actions as $index => $action) {
            $active = array_key_exists('active', $action) ? $action['active'] : true; // assume true
            if (true === $active) {
                $allInactive = false;
            }
            if (false === $active) {
                $inactiveIndex = $index;
            }
        }
        if (true === $allInactive) {
            $validator->errors()->add(sprintf('actions.%d.active', $inactiveIndex), (string)trans('validation.at_least_one_active_action'));
        }
    }
}
