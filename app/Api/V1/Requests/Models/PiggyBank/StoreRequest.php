<?php

/**
 * PiggyBankStoreRequest.php
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

use FireflyIII\Rules\IsValidPositiveAmount;
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
        $fields                     = [
            'order' => ['order', 'convertInteger'],
        ];
        $data                       = $this->getAllData($fields);
        $data['name']               = $this->convertString('name');
        $data['account_id']         = $this->convertInteger('account_id');
        $data['targetamount']       = $this->convertString('target_amount');
        $data['current_amount']     = $this->convertString('current_amount');
        $data['startdate']          = $this->getCarbonDate('start_date');
        $data['targetdate']         = $this->getCarbonDate('target_date');
        $data['notes']              = $this->stringWithNewlines('notes');
        $data['object_group_id']    = $this->convertInteger('object_group_id');
        $data['object_group_title'] = $this->convertString('object_group_title');

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'name'               => 'required|min:1|max:255|uniquePiggyBankForUser',
            'current_amount'     => ['nullable', new IsValidPositiveAmount()],
            'account_id'         => 'required|numeric|belongsToUser:accounts,id',
            'object_group_id'    => 'numeric|belongsToUser:object_groups,id',
            'object_group_title' => ['min:1', 'max:255'],
            'target_amount'      => ['required', new IsValidPositiveAmount()],
            'start_date'         => 'date|nullable',
            'target_date'        => 'date|nullable|after:start_date',
            'notes'              => 'max:65000',
        ];
    }
}
