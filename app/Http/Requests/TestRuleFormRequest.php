<?php
/**
 * TestRuleFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Support\Request\GetRuleConfiguration;

/**
 * Class TestRuleFormRequest.
 *
 * @codeCoverageIgnore
 */
class TestRuleFormRequest extends LoggedInRequest
{
    use GetRuleConfiguration;

    /**
     * Rules for this request.
     * TODO these rules are not valid anymore.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        $validTriggers = $this->getTriggers();
        $rules         = [
            'rule-trigger.*'       => 'required|min:1|in:' . implode(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
        ];

        return $rules;
    }
}
