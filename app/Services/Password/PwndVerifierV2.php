<?php
/**
 * PwndVerifierV2.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Password;

use Exception;
use Log;
use Requests;

/**
 * Class PwndVerifierV2.
 */
class PwndVerifierV2 implements Verifier
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
        $hash   = sha1($password);
        $prefix = substr($hash, 0, 5);
        $rest   = substr($hash, 5);
        $uri    = sprintf('https://api.pwnedpasswords.com/range/%s', $prefix);
        $opt    = ['useragent' => 'Firefly III v' . config('firefly.version'), 'timeout' => 2];

        Log::debug(sprintf('hash prefix is %s', $prefix));
        Log::debug(sprintf('rest is %s', $rest));

        try {
            $result = Requests::get($uri, $opt);
        } catch (Exception $e) {
            return true;
        }
        Log::debug(sprintf('Status code returned is %d', $result->status_code));
        if (404 === $result->status_code) {
            return true;
        }
        $strpos = stripos($result->body, $rest);
        if ($strpos === false) {
            Log::debug(sprintf('%s was not found in result body. Return true.', $rest));

            return true;
        }
        Log::debug('Could not find %s, return FALSE.');

        return false;
    }
}
