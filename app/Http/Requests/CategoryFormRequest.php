<?php
/**
 * CategoryFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Category;
use Input;

/**
 * Class CategoryFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class CategoryFormRequest extends Request
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

        $nameRule = 'required|between:1,100|uniqueObjectForUser:categories,name';
        if (Category::find(Input::get('id'))) {
            $nameRule = 'required|between:1,100|uniqueObjectForUser:categories,name,' . intval(Input::get('id'));
        }

        return [
            'name' => $nameRule,
        ];
    }
}
