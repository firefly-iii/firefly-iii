<?php

/**
 * PiggyBankUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\PiggyBank;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Rules\IsAssetAccountId;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Rules\LessThanPiggyTarget;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $fields = [
            'name'               => ['name', 'convertString'],
            'account_id'         => ['account_id', 'convertInteger'],
            'targetamount'       => ['target_amount', 'convertString'],
            'current_amount'     => ['current_amount', 'convertString'],
            'startdate'          => ['start_date', 'convertDateTime'],
            'targetdate'         => ['target_date', 'convertDateTime'],
            'notes'              => ['notes', 'stringWithNewlines'],
            'order'              => ['order', 'convertInteger'],
            'object_group_title' => ['object_group_title', 'convertString'],
            'object_group_id'    => ['object_group_id', 'convertInteger'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var PiggyBank $piggyBank */
        $piggyBank = $this->route()->parameter('piggyBank');

        return [
            'name'           => 'min:1|max:255|uniquePiggyBankForUser:'.$piggyBank->id,
            'current_amount' => ['nullable', new LessThanPiggyTarget(), new IsValidPositiveAmount()],
            'target_amount'  => ['nullable', new IsValidPositiveAmount()],
            'start_date'     => 'date|nullable',
            'target_date'    => 'date|nullable|after:start_date',
            'notes'          => 'max:65000',
            'account_id'     => ['belongsToUser:accounts', new IsAssetAccountId()],
        ];
    }
}
