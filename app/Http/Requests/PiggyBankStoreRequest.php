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

namespace FireflyIII\Http\Requests;

use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class PiggyBankStoreRequest.
 */
class PiggyBankStoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Returns the data required by the controller.
     */
    public function getPiggyBankData(): array
    {
        $data = [
            'name'               => $this->convertString('name'),
            'start_date'          => $this->getCarbonDate('start_date'),
            //'account_id'         => $this->convertInteger('account_id'),
            'accounts' => $this->get('accounts'),
            'target_amount'       => $this->convertString('target_amount'),
            'target_date'         => $this->getCarbonDate('target_date'),
            'notes'              => $this->stringWithNewlines('notes'),
            'object_group_title' => $this->convertString('object_group'),
        ];
        if(!is_array($data['accounts'])) {
            $data['accounts'] = [];
        }

        return $data;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'name'         => 'required|min:1|max:255|uniquePiggyBankForUser',
            'accounts'   => 'required|array',
            'accounts.*'   => 'required|belongsToUser:accounts',
            'target_amount' => ['nullable', new IsValidPositiveAmount()],
            'start_date'    => 'date',
            'target_date'   => 'date|nullable',
            'order'        => 'integer|min:1',
            'object_group' => 'min:0|max:255',
            'notes'        => 'min:1|max:32768|nullable',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // need to have more than one account.
        // accounts need to have the same currency or be multi-currency(?).


        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
