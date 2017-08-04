<?php
/**
 * PwndVerifier.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Password;

use Log;
use Requests;
use Requests_Exception;

/**
 * Class PwndVerifier
 *
 * @package FireflyIII\Support\Password
 */
class PwndVerifier implements Verifier
{

    /**
     * Verify the given password against (some) service.
     *
     * @param string $password
     *
     * @return bool
     */
    public function validPassword(string $password): bool
    {
        $hash = sha1($password);
        $uri  = sprintf('https://haveibeenpwned.com/api/v2/pwnedpassword/%s', $hash);
        $opt  = ['useragent' => 'Firefly III v' . config('firefly.version'), 'timeout' => 2];

        try {
            $result = Requests::get($uri, ['originalPasswordIsAHash' => 'true'], $opt);
        } catch (Requests_Exception $e) {
            return true;
        }
        Log::debug(sprintf('Status code returned is %d', $result->status_code));
        if ($result->status_code === 404) {
            return true;
        }

        return false;
    }
}