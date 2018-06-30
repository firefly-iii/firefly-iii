<?php
/**
 * RuleGroupRequest.php
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

use FireflyIII\Models\RuleGroup;


/**
 *
 * Class RuleGroupRequest
 */
class RuleGroupRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return [
            'title'       => $this->string('title'),
            'description' => $this->string('description'),
            'active'      => $this->boolean('active'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'title'       => 'required|between:1,100|uniqueObjectForUser:rule_groups,title',
            'description' => 'between:1,5000|nullable',
            'active'      => 'required|boolean',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var RuleGroup $ruleGroup */
                $ruleGroup      = $this->route()->parameter('ruleGroup');
                $rules['title'] = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . $ruleGroup->id;
                break;
        }

        return $rules;
    }
}