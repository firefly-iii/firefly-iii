<?php
/**
 * RuleGroupFormRequest.php
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

use FireflyIII\Models\RuleGroup;
use FireflyIII\Rules\IsBoolean;

/**
 * Class RuleGroupFormRequest.
 */
class RuleGroupFormRequest extends Request
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
     */
    public function getRuleGroupData(): array
    {
        $active = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }

        return [
            'title'       => $this->string('title'),
            'description' => $this->nlString('description'),
            'active'      => $active,
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';

        /** @var RuleGroup $ruleGroup */
        $ruleGroup = $this->route()->parameter('ruleGroup');

        if (null !== $ruleGroup) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . $ruleGroup->id;
        }

        return [
            'title'       => $titleRule,
            'description' => 'between:1,5000|nullable',
            'active'      => [new IsBoolean],
        ];
    }
}
