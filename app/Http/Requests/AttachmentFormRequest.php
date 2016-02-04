<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class AttachmentFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class AttachmentFormRequest extends Request
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
            'title'       => 'between:1,255',
            'description' => 'between:1,65536',
            'notes'       => 'between:1,65536',
        ];
    }
}
