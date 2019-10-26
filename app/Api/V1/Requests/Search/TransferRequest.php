<?php
/**
 * TransferRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Requests\Search;


use FireflyIII\Api\V1\Requests\Request;
use FireflyIII\Rules\IsTransferAccount;

/**
 * Class TransferRequest
 */
class TransferRequest extends Request
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'source'      => ['required', new IsTransferAccount],
            'destination' => ['required', new IsTransferAccount],
            'amount'      => 'required|numeric|more:0',
            'description' => 'required|min:1',
            'date'        => 'required|date',
        ];
    }

}