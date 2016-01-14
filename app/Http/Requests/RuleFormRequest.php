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
        $contextActions = Config::get('firefly.rule-actions-text');

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (RuleGroup::find(Input::get('id'))) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval(Input::get('id'));
        }

        $rules = [
            'title'                => $titleRule,
            'description'          => 'between:1,5000',
            'stop_processing'      => 'boolean',
            'trigger'              => 'required|in:store-journal,update-journal',
            'rule-trigger.*'       => 'required|in:' . join(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1',
            'rule-action.*'        => 'required|in:' . join(',', $validActions),
            'rule-action-value.*'  => 'required_if:rule-action.*,' . join(',', $contextActions)
        ];

    }
}
