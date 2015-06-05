<?php

namespace FireflyIII\Http\Requests;

use Auth;
use FireflyIII\Models\Category;
use Input;

/**
 * Class CategoryFormRequest
 *
 * @codeCoverageIgnore
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
        return Auth::check();
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
