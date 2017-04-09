<?php
/**
 * StartFireflySession.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;

/**
 * Class StartFireflySession
 *
 * @package FireflyIII\Http\Middleware
 */
class StartFireflySession extends StartSession
{

    /**
     * Create a new session middleware.
     *
     * @param  \Illuminate\Session\SessionManager $manager
     */
    public function __construct(SessionManager $manager)
    {
        parent::__construct($manager);
    }

    /**
     * Store the current URL for the request if necessary.
     *
     * @param  \Illuminate\Http\Request              $request
     * @param  \Illuminate\Contracts\Session\Session $session
     *
     * @return void
     */
    protected function storeCurrentUrl(Request $request, $session)
    {
        $fullUrl = $request->fullUrl();
        if ($request->method() === 'GET' && $request->route() && !$request->ajax()) {
            if (strpos($fullUrl, '/javascript/') === false) {
                $session->setPreviousUrl($fullUrl);
            }
        }
    }

}
