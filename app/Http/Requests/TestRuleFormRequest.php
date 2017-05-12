<?php
/**
 * TestRuleFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class RuleFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class TestRuleFormRequest extends Request
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
    public function rules()
    {

        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $rules         = [
            'rule-trigger.*'       => 'required|min:1|in:' . join(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
        ];

        return $rules;
    }
}
