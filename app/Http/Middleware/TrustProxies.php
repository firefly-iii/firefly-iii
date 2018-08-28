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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

/**
 * Class TrustProxies
 *
 * @codeCoverageIgnore
 */
class TrustProxies extends Middleware
{
    /** @var int The headers to check. */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;

    /**
     * TrustProxies constructor.
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $trustedProxies = (string)env('TRUSTED_PROXIES', null);
        $this->proxies  = explode(',', $trustedProxies);
        if ('**' === $trustedProxies) {
            $this->proxies = '**';
        }
        parent::__construct($config);
    }
}
