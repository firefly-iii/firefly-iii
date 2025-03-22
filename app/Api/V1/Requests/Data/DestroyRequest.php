<?php

/**
 * DataDestroyRequest.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Data;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DestroyRequest
 */
class DestroyRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getObjects(): string
    {
        return $this->get('objects') ?? '';
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        $valid = 'budgets,bills,piggy_banks,rules,recurring,categories,tags,object_groups'
                 .',accounts,asset_accounts,expense_accounts,revenue_accounts,liabilities,transactions,withdrawals,deposits,transfers'
                 .',not_assets_liabilities';

        return [
            'objects' => sprintf('required|max:255|min:1|string|in:%s', $valid),
            'unused'  => 'in:true,false',
        ];
    }
}
