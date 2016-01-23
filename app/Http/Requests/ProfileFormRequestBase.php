<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileFormRequestBase
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ProfileFormRequestBase extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }
}