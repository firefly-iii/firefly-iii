<?php
/**
 * ImportUploadRequest.php
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

/**
 * Class ImportUploadRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class ImportUploadRequest extends Request
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
        $types = array_keys(config('firefly.import_formats'));

        return [
            'import_file'        => 'required|file',
            'import_file_type'   => 'required|in:' . join(',', $types),
            'configuration_file' => 'file',
        ];
    }
}
