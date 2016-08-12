<?php
/**
 * ImportUploadRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;

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
        return Auth::check();
    }

    /**
     * @return array
     */
    public function rules()
    {
        $types = array_keys(config('firefly.import_formats'));

        return [
            'import_file'      => 'required|file',
            'import_file_type' => 'required|in:' . join(',', $types),
        ];

    }
}
