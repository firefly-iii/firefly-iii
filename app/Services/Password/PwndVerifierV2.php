<?php
/**
 * PwndVerifierV2.php
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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Log;
use RuntimeException;

/**
 * Class PwndVerifierV2.
 *
 * @codeCoverageIgnore
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
        // Yes SHA1 is unsafe but in this context its fine.
        $hash   = sha1($password);
        $prefix = substr($hash, 0, 5);
        $rest   = substr($hash, 5);
        $uri    = sprintf('https://api.pwnedpasswords.com/range/%s', $prefix);
        $opt    = [
            'headers' => [
                'User-Agent'  => sprintf('Firefly III v%s', config('firefly.version')),
                'Add-Padding' => 'true',
            ],
            'timeout' => 3.1415];

        Log::debug(sprintf('hash prefix is %s', $prefix));
        Log::debug(sprintf('rest is %s', $rest));

        try {
            $client = new Client();
            $res    = $client->request('GET', $uri, $opt);
        } catch (GuzzleException | RequestException $e) {
            Log::error(sprintf('Could not verify password security: %s', $e->getMessage()));

            return true;
        }
        Log::debug(sprintf('Status code returned is %d', $res->getStatusCode()));
        if (404 === $res->getStatusCode()) {
            return true;
        }
        try {
            $strpos = stripos($res->getBody()->getContents(), $rest);
        } catch (RuntimeException $e) { // @phpstan-ignore-line
            Log::error(sprintf('Could not get body from Pwnd result: %s', $e->getMessage()));
            $strpos = false;
        }
        if (false === $strpos) {
            Log::debug(sprintf('%s was not found in result body. Return true.', $rest));

            return true;
        }
        Log::debug(sprintf('Found %s, return FALSE.', $rest));

        return false;
    }
}
