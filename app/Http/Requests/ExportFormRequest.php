<?php
/**
 * ExportFormRequest.php
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
class ExportFormRequest extends Request
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
        $sessionFirst = clone session('first');
        $first        = $sessionFirst->subDay()->format('Y-m-d');
        $today        = Carbon::create()->addDay()->format('Y-m-d');
        $formats      = join(',', array_keys(config('firefly.export_formats')));

        // fixed

        return [
            'export_start_range'  => 'required|date|after:' . $first,
            'export_end_range'    => 'required|date|before:' . $today,
            'accounts'            => 'required',
            'job'                 => 'required|belongsToUser:export_jobs,key',
            'accounts.*'          => 'required|exists:accounts,id|belongsToUser:accounts',
            'include_attachments' => 'in:0,1',
            'include_config'      => 'in:0,1',
            'exportFormat'        => 'in:' . $formats,
        ];
    }
}
