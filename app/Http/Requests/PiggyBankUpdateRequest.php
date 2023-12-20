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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PiggyBankFormRequest.
 */
class PiggyBankUpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Returns the data required by the controller.
     */
    public function getPiggyBankData(): array
    {
        return [
            'name'               => $this->convertString('name'),
            'startdate'          => $this->getCarbonDate('startdate'),
            'account_id'         => $this->convertInteger('account_id'),
            'targetamount'       => trim($this->convertString('targetamount')),
            'targetdate'         => $this->getCarbonDate('targetdate'),
            'notes'              => $this->stringWithNewlines('notes'),
            'object_group_title' => $this->convertString('object_group'),
        ];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        /** @var PiggyBank $piggy */
        $piggy = $this->route()->parameter('piggyBank');

        return [
            'name'         => sprintf('required|between:1,255|uniquePiggyBankForUser:%d', $piggy->id),
            'account_id'   => 'required|belongsToUser:accounts',
            'targetamount' => 'nullable|numeric|max:1000000000',
            'startdate'    => 'date',
            'targetdate'   => 'date|nullable',
            'order'        => 'integer|max:65536|min:1',
            'object_group' => 'min:0|max:255',
        ];
    }
}
