<?php
/**
 * RuleFormRequest.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Requests;

use Auth;
use Config;
use FireflyIII\Models\RuleGroup;
use Input;

/**
 * Class RuleFormRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class RuleFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    /**
     * @return array
     */
    public function rules()
    {

        $validTriggers = array_keys(Config::get('firefly.rule-triggers'));
        $validActions  = array_keys(Config::get('firefly.rule-actions'));

        // some actions require text:
        $contextActions = join(',', Config::get('firefly.rule-actions-text'));

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (RuleGroup::find(Input::get('id'))) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval(Input::get('id'));
        }

        $rules = [
            'title'                => $titleRule,
            'description'          => 'between:1,5000',
            'stop_processing'      => 'boolean',
            'rule_group_id'        => 'required|belongsToUser:rule_groups',
            'trigger'              => 'required|in:store-journal,update-journal',
            'rule-trigger.*'       => 'required|in:' . join(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
            'rule-action.*'        => 'required|in:' . join(',', $validActions),
        ];
        // since Laravel does not support this stuff yet, here's a trick.
        for ($i = 0; $i < 10; $i++) {
            $rules['rule-action-value.' . $i] = 'required_if:rule-action.' . $i . ',' . $contextActions . '|ruleActionValue';
        }

        return $rules;
    }
}
