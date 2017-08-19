<?php
/**
 * DeleteDeviceSessionRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Token\InstallationToken;
use FireflyIII\Services\Bunq\Token\SessionToken;
use Log;

/**
 * Class DeleteDeviceSessionRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class DeleteDeviceSessionRequest extends BunqRequest
{
    /** @var  SessionToken */
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