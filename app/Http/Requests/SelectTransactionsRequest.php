<?php
/**
 * SelectTransactionsRequest.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;

/**
 * Class SelectTransactionsRequest.
 *
 * @codeCoverageIgnore
 */
class SelectTransactionsRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        /** @var Carbon $sessionFirst */
        $sessionFirst = clone session('first');
        $first        = $sessionFirst->subDay()->format('Y-m-d');
        $today        = Carbon::now()->addDay()->format('Y-m-d');

        return [
            'start_date' => 'required|date|after:' . $first,
            'end_date'   => 'required|date|before:' . $today,
            'accounts'   => 'required',
            'accounts.*' => 'required|exists:accounts,id|belongsToUser:accounts',
        ];
    }
}
