<?php

/**
 * StartFireflySession.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

/**
 * Class StartFireflySession.
 */
class StartFireflySession extends StartSession
{
    /**
     * Store the current URL for the request if necessary.
     *
     * @param Session $session
     */
    #[\Override]
    protected function storeCurrentUrl(Request $request, $session): void
    {
        $url     = $request->fullUrl();
        $safeUrl = app('steam')->getSafeUrl($url, route('index'));

        if ($url !== $safeUrl) {
            return;
        }

        if ('GET' === $request->method() && !$request->ajax()) {
            $session->setPreviousUrl($safeUrl);
        }
    }
}
