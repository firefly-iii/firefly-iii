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

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (RuleGroup::find(Input::get('id'))) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval(Input::get('id'));
        }

        return [
            'title'           => $titleRule,
            'description'     => 'between:1,5000',
            'stop_processing' => 'boolean',
            'trigger'         => 'required|in:store-journal,update-journal',
            'rule-trigger.*'  => 'required|in:' . join(',', $validTriggers),
            'rule-trigger-value.*'  => 'required|min:1'

            
        ];
    }
}
