<?php

/**
 * RuleGroupStoreRequest.php
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

use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $active = true;
        $order  = 31337;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('order')) {
            $order = $this->convertInteger('order');
        }

        return [
            'title'       => $this->convertString('title'),
            'description' => $this->convertString('description'),
            'active'      => $active,
            'order'       => $order,
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'title'       => 'required|min:1|max:100|uniqueObjectForUser:rule_groups,title',
            'description' => 'min:1|max:32768|nullable',
            'active'      => [new IsBoolean()],
        ];
    }
}
