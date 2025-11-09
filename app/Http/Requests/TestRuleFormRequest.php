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

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\GetRuleConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class TestRuleFormRequest.
 */
class TestRuleFormRequest extends FormRequest
{
    use ChecksLogin;
    use GetRuleConfiguration;

    /**
     * Rules for this request.
     * TODO these rules are not valid anymore.
     */
    public function rules(): array
    {
        // fixed
        $validTriggers = $this->getTriggers();

        return [
            'rule-trigger.*'       => 'required|max:1024|min:1|in:'.implode(',', $validTriggers),
            'rule-trigger-value.*' => 'required|max:1024|min:1|ruleTriggerValue',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
