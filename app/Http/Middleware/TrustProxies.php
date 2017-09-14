<?php
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
}
