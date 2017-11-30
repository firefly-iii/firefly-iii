<?php
/**
 * SelectTransactionsRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;

/**
 * Class ExportFormRequest.
 */
class SelectTransactionsRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed
        $sessionFirst = clone session('first');
        $first        = $sessionFirst->subDay()->format('Y-m-d');
        $today        = Carbon::create()->addDay()->format('Y-m-d');

        return [
            'start_date' => 'required|date|after:' . $first,
            'end_date'   => 'required|date|before:' . $today,
            'accounts'   => 'required',
            'accounts.*' => 'required|exists:accounts,id|belongsToUser:accounts',
        ];
    }
}
