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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getRuleData(): array
    {
        return [
            'title'               => $this->string('title'),
            'rule_group_id'       => $this->integer('rule_group_id'),
            'active'              => $this->boolean('active'),
            'trigger'             => $this->string('trigger'),
            'description'         => $this->string('description'),
            'rule-triggers'       => $this->get('rule-trigger'),
            'rule-trigger-values' => $this->get('rule-trigger-value'),
            'rule-trigger-stop'   => $this->get('rule-trigger-stop'),
            'rule-actions'        => $this->get('rule-action'),
            'rule-action-values'  => $this->get('rule-action-value'),
            'rule-action-stop'    => $this->get('rule-action-stop'),
            'stop_processing'     => $this->boolean('stop_processing'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var RuleRepositoryInterface $repository */
        $repository    = app(RuleRepositoryInterface::class);
        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $validActions  = array_keys(config('firefly.rule-actions'));

        // some actions require text:
        $contextActions = join(',', config('firefly.rule-actions-text'));

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title';
        if (null !== $repository->find(intval($this->get('id')))->id) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rules,title,' . intval($this->get('id'));
        }
        $rules = [
            'title'                => $titleRule,
            'description'          => 'between:1,5000|nullable',
            'stop_processing'      => 'boolean',
            'rule_group_id'        => 'required|belongsToUser:rule_groups',
            'trigger'              => 'required|in:store-journal,update-journal',
            'rule-trigger.*'       => 'required|in:' . join(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
            'rule-action.*'        => 'required|in:' . join(',', $validActions),
        ];
        // since Laravel does not support this stuff yet, here's a trick.
        for ($i = 0; $i < 10; ++$i) {
            $rules['rule-action-value.' . $i] = 'required_if:rule-action.' . $i . ',' . $contextActions . '|ruleActionValue';
        }

        return $rules;
    }
}
