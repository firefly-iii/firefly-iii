<?php
/**
 * PwndVerifierV3.php
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

namespace FireflyIII\Services\Password;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;

/**
 * Class PwndVerifierV3
 * @codeCoverageIgnore
 */
class PwndVerifierV3 implements Verifier
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
        Log::debug('Now in API v3.');
        $hash   = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $rest   = substr($hash, 5);
        $uri    = sprintf('https://api.pwnedpasswords.com/%s/%s', 'range', $prefix);

        Log::debug(sprintf('URI is %s', $uri));

        $headers = [
            'User-Agent'   => sprintf('Firefly III v%s', config('firefly.version')),
        ];
        Log::debug('Headers', $headers);
        $opts    = [
            'headers' => $headers,
            'timeout' => 5,
        ];

        Log::debug(sprintf('hash prefix is %s', $prefix));
        Log::debug(sprintf('rest is %s', $rest));

        try {
            $client = new Client;
            $res    = $client->request('GET', $uri, $opts);
        } catch (GuzzleException|Exception $e) {
            Log::error(sprintf('Could not verify password security: %s', $e->getMessage()));
            return true;
        }
        Log::debug(sprintf('Status code returned is %d', $res->getStatusCode()));
        if (404 === $res->getStatusCode()) {
            return true;
        }
        $body = $res->getBody()->getContents();
        try {
            $strpos = stripos($body, $rest);
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from Pwnd result: %s', $e->getMessage()));
            $strpos = false;
        }
        if (false === $strpos) {
            Log::debug(sprintf('%s was not found in result body. Return true.', $rest));
            return true;
        }
        Log::debug(sprintf('Found %s, so return FALSE.', $rest));
        return false;
    }
}
