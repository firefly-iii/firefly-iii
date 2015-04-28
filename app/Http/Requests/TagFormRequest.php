<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 27/04/15
 * Time: 12:50
 */

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class TagFormRequest
 *
 * @package FireflyIII\Http\Requests
 */
class TagFormRequest extends Request
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
        return [
            'tag'         => 'required|min:1|uniqueObjectForUser:tags,tag,TRUE',
            'description' => 'min:1',
            'date'        => 'date',
            'latitude'    => 'numeric|min:-90|max:90',
            'longitude'   => 'numeric|min:-90|max:90',
            'tagMode'     => 'required|in:nothing,balancingAct,advancePayment'
        ];
    }
}