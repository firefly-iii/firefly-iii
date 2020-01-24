<?php
/**
 * PiggyBankRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Rules\IsAssetAccountId;
use FireflyIII\Rules\LessThanPiggyTarget;
use FireflyIII\Rules\ZeroOrMore;

/**
 *
 * Class PiggyBankRequest
 *
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class PiggyBankRequest extends Request
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
        return [
            'name'           => $this->string('name'),
            'account_id'     => $this->integer('account_id'),
            'targetamount'   => $this->string('target_amount'),
            'current_amount' => $this->string('current_amount'),
            'startdate'      => $this->date('start_date'),
            'targetdate'     => $this->date('target_date'),
            'notes'          => $this->nlString('notes'),
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
            'name'           => 'required|between:1,255|uniquePiggyBankForUser',
            'current_amount' => ['numeric', new ZeroOrMore, 'lte:target_amount'],
            'start_date'     => 'date|nullable',
            'target_date'    => 'date|nullable|after:start_date',
            'notes'          => 'max:65000',
        ];

        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var PiggyBank $piggyBank */
                $piggyBank               = $this->route()->parameter('piggyBank');
                $rules['name']           = 'between:1,255|uniquePiggyBankForUser:' . $piggyBank->id;
                $rules['account_id']     = ['belongsToUser:accounts', new IsAssetAccountId];
                $rules['target_amount']  = 'numeric|more:0';
                $rules['current_amount'] = ['numeric', new ZeroOrMore, new LessThanPiggyTarget];
                break;
        }


        return $rules;
    }

}
