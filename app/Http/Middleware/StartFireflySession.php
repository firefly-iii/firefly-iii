<?php
/**
 * StartFireflySession.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Log;

/**
 * Class StartFireflySession.
 *
 * @codeCoverageIgnore
 */
class StartFireflySession extends StartSession
{
    /**
     * Store the current URL for the request if necessary.
     *
     * @param \Illuminate\Http\Request              $request
     * @param \Illuminate\Contracts\Session\Session $session
     */
    protected function storeCurrentUrl(Request $request, $session): void
    {
        $uri          = $request->fullUrl();
        $isScriptPage = strpos($uri, 'jscript');
        $isDeletePage = strpos($uri, 'delete');
        $isLoginPage  = strpos($uri, '/login');

        // also stop remembering "delete" URL's.

        if (false === $isScriptPage && false === $isDeletePage
            && false === $isLoginPage
            && 'GET' === $request->method()
            && !$request->ajax()) {
            $session->setPreviousUrl($uri);
            //Log::debug(sprintf('Will set previous URL to %s', $uri));

            return;
        }
        //Log::debug(sprintf('Will NOT set previous URL to %s', $uri));
    }
}
