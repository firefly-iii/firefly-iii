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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Rules\IsAssetAccountId;
use FireflyIII\Rules\LessThanPiggyTarget;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PiggyBankUpdateRequest
 *
 * @codeCoverageIgnore
 */
class PiggyBankUpdateRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        // if the value isn't present, dont return it at all.
        // TODO this should be the way to collect fields for all API things.
        // TODO make sure piggy bank uses 'start_date' etc. until right up to DB update.
        // TODO can we configure this and return it from config?
        $return = [];
        $fields = [
            'name'            => ['name', 'string'],
            'account_id'      => ['account_id', 'integer'],
            'targetamount'    => ['target_amount', 'string'],
            'current_amount'  => ['current_amount', 'string'],
            'startdate'       => ['start_date', 'date'],
            'targetdate'      => ['target_date', 'string'],
            'notes'           => ['notes', 'nlString'],
            'order'           => ['order', 'integer'],
            'object_group'    => ['object_group', 'string'],
            'object_group_id' => ['object_group_id', 'integer'],
        ];
        foreach ($fields as $field => $info) {
            if ($this->has($info[0])) {
                $method         = $info[1];
                $return[$field] = $this->$method($info[0]);
            }
        }

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $piggyBank = $this->route()->parameter('piggyBank');

        return [
            'name'           => 'between:1,255|uniquePiggyBankForUser:' . $piggyBank->id,
            'current_amount' => ['numeric', 'gte:0', new LessThanPiggyTarget],
            'target_amount'  => 'numeric|gt:0',
            'start_date'     => 'date|nullable',
            'target_date'    => 'date|nullable|after:start_date',
            'notes'          => 'max:65000',
            'account_id'     => ['belongsToUser:accounts', new IsAssetAccountId],
        ];
    }

}
