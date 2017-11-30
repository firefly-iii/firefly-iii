<?php
/**
 * DeleteDeviceSessionRequest.php
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

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Token\SessionToken;
use Log;

/**
 * Class DeleteDeviceSessionRequest.
 */
class DeleteDeviceSessionRequest extends BunqRequest
{
    /** @var SessionToken */
    private $sessionToken;

    /**
     *
     */
    public function call(): void
    {
        Log::debug('Going to send bunq delete session request.');
        $uri                                     = sprintf('/v1/session/%d', $this->sessionToken->getId());
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->sessionToken->getToken();
        $this->sendSignedBunqDelete($uri, $headers);

        return;
    }

    /**
     * @param SessionToken $sessionToken
     */
    public function setSessionToken(SessionToken $sessionToken)
    {
        $this->sessionToken = $sessionToken;
    }
}
