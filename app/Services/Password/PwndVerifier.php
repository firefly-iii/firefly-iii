<?php
/**
 * PwndVerifier.php
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

namespace FireflyIII\Services\Password;

use Log;
use Requests;
use Requests_Exception;

/**
 * Class PwndVerifier
 *
 * @package FireflyIII\Services\Password
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
