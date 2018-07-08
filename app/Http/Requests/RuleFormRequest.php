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

use FireflyIII\Repositories\Rule\RuleRepositoryInterface;

/**
 * Class RuleFormRequest.
 */
class RuleFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getRuleData(): array
    {
        $data          = [
            'title'           => $this->string('title'),
            'rule_group_id'   => $this->integer('rule_group_id'),
            'active'          => $this->boolean('active'),
            'trigger'         => $this->string('trigger'),
            'description'     => $this->string('description'),
            'stop-processing' => $this->boolean('stop_processing'),
            'strict'          => $this->boolean('strict'),
            'rule-triggers'   => [],
            'rule-actions'    => [],
        ];
        $triggers      = $this->get('rule-trigger');
        $triggerValues = $this->get('rule-trigger-value');
        $triggerStop   = $this->get('rule-trigger-stop');

        $actions      = $this->get('rule-action');
        $actionValues = $this->get('rule-action-value');
        $actionStop   = $this->get('rule-action-stop');

        if (\is_array($triggers)) {
            foreach ($triggers as $index => $value) {
                $data['rule-triggers'][] = [
                    'name'            => $value,
                    'value'           => $triggerValues[$index] ?? '',
                    'stop-processing' => 1 === (int)($triggerStop[$index] ?? 0),
                ];
            }
        }

        if (\is_array($actions)) {
            foreach ($actions as $index => $value) {
                $data['rule-actions'][] = [
                    'name'            => $value,
                    'value'           => $actionValues[$index] ?? '',
                    'stop-processing' => 1 === (int)($actionStop[$index] ?? 0),
                ];
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        /** @var RuleRepositoryInterface $repository */
        $repository    = app(RuleRepositoryInterface::class);
        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $validActions  = array_keys(config('firefly.rule-actions'));

        // some actions require text:
        $contextActions = implode(',', config('firefly.rule-actions-text'));

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title';
        if (null !== $repository->find((int)$this->get('id'))->id) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title,' . (int)$this->get('id');
        }
        $rules = [
            'title'                => $titleRule,
            'description'          => 'between:1,5000|nullable',
            'stop_processing'      => 'boolean',
            'rule_group_id'        => 'required|belongsToUser:rule_groups',
            'trigger'              => 'required|in:store-journal,update-journal',
            'rule-trigger.*'       => 'required|in:' . implode(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
            'rule-action.*'        => 'required|in:' . implode(',', $validActions),
            'strict'               => 'in:0,1',
        ];
        // since Laravel does not support this stuff yet, here's a trick.
        for ($i = 0; $i < 10; ++$i) {
            $rules['rule-action-value.' . $i] = 'required_if:rule-action.' . $i . ',' . $contextActions . '|ruleActionValue';
        }

        return $rules;
    }
}
