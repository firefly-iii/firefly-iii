<?php
declare(strict_types = 1);
/**
 * UserEventListener.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use Session;

/**
 * Class UserEventListener
 *
 * @package FireflyIII\Handlers\Events
 */
class UserEventListener
{
    /**
     * Handle user logout events.
     * 
     * @return bool
     */
    public function onUserLogout(): bool
    {
        // dump stuff from the session:
        Session::forget('twofactor-authenticated');
        Session::forget('twofactor-authenticated-date');

        return true;
    }
}
