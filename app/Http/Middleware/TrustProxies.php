<?php
/**
 * TrustProxies.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

/**
 * TrustProxies.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The current proxy header mappings.
     *
     * @var array
     */
    protected $headers
        = [
            Request::HEADER_FORWARDED         => 'FORWARDED',
            Request::HEADER_X_FORWARDED_FOR   => 'X_FORWARDED_FOR',
            Request::HEADER_X_FORWARDED_HOST  => 'X_FORWARDED_HOST',
            Request::HEADER_X_FORWARDED_PORT  => 'X_FORWARDED_PORT',
            Request::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
        ];
    /**
     * The trusted proxies for this application.
     *
     * @var array
     */
    protected $proxies;

    /**
     * TrustProxies constructor.
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $trustedProxies = env('TRUSTED_PROXIES', null);
        if (null !== $trustedProxies && strlen($trustedProxies) > 0) {
            $this->proxies = $trustedProxies;
        }

        parent::__construct($config);
    }
}
