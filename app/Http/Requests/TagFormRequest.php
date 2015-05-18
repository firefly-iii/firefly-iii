<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 27/04/15
 * Time: 12:50
 */

namespace FireflyIII\Http\Requests;

use Auth;
use FireflyIII\Models\Tag;
use Input;

/**
 * Class TagFormRequest
 *
 * @codeCoverageIgnore
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
        $idRule  = '';
        $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,TRUE';
        if (Tag::find(Input::get('id'))) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,TRUE,' . Input::get('id');
        }

        return [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'min:1',
            'date'        => 'date',
            'latitude'    => 'numeric|min:-90|max:90',
            'longitude'   => 'numeric|min:-90|max:90',
            'zoomLevel'   => 'numeric|min:0|max:80',
            'tagMode'     => 'required|in:nothing,balancingAct,advancePayment'
        ];
    }
}
