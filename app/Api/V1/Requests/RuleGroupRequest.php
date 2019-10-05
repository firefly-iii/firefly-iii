<?php
/**
 * RuleGroupRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\RuleGroup;
use FireflyIII\Rules\IsBoolean;


/**
 * @codeCoverageIgnore
 * Class RuleGroupRequest
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class RuleGroupRequest extends Request
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
        $active = true;

        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }

        return [
            'title'       => $this->string('title'),
            'description' => $this->string('description'),
            'active'      => $active,
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'title'       => 'required|between:1,100|uniqueObjectForUser:rule_groups,title',
            'description' => 'between:1,5000|nullable',
            'active'      => [new IsBoolean],
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
