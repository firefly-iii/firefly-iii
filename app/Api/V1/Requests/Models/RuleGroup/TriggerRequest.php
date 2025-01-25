<?php

/**
 * RuleGroupTriggerRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\RuleGroup;

use Carbon\Carbon;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TriggerRequest
 */
class TriggerRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getTriggerParameters(): array
    {
        return [
            'start'    => $this->getDate('start'),
            'end'      => $this->getDate('end'),
            'accounts' => $this->getAccounts(),
        ];
    }

    private function getDate(string $field): ?Carbon
    {
        $value = $this->query($field);
        if (is_array($value)) {
            return null;
        }
        $value = (string) $value;

        return null === $this->query($field) ? null : Carbon::createFromFormat('Y-m-d', substr($value, 0, 10));
    }

    private function getAccounts(): array
    {
        if (null === $this->get('accounts')) {
            return [];
        }

        return $this->get('accounts');
    }

    public function rules(): array
    {
        return [
            'start' => 'date|after:1900-01-01|before:2099-12-31',
            'end'   => 'date|after_or_equal:start|after:1900-01-01|before:2099-12-31',
        ];
    }
}
