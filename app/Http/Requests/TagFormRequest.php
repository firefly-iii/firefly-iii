<?php
declare(strict_types = 1);
namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Tag;
use Input;

/**
 * Class TagFormRequest
 *
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
    public function collectTagData() :array
    {
        if (Input::get('setTag') == 'true') {
            $latitude  = $this->get('latitude');
            $longitude = $this->get('longitude');
            $zoomLevel = $this->get('zoomLevel');
        } else {
            $latitude  = null;
            $longitude = null;
            $zoomLevel = null;
        }
        $date = $this->get('date') ?? '';

        $data = [
            'tag'         => $this->get('tag'),
            'date'        => strlen($date) > 0 ? new Carbon($date) : null,
            'description' => $this->get('description') ?? '',
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
            'tagMode'     => $this->get('tagMode'),
        ];

        return $data;


    }

    /**
     * @return array
     */
    public function rules()
    {
        $idRule  = '';
        $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag';
        if (Tag::find(Input::get('id'))) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,' . Input::get('id');
        }

        return [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'min:1',
            'date'        => 'date',
            'latitude'    => 'numeric|min:-90|max:90',
            'longitude'   => 'numeric|min:-90|max:90',
            'zoomLevel'   => 'numeric|min:0|max:80',
            'tagMode'     => 'required|in:nothing,balancingAct,advancePayment',
        ];
    }
}
