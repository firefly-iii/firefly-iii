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

/**
 * Class PwndVerifierV2.
 */
class PwndVerifierV2 implements Verifier
{
    /**
     * Verify the given password against (some) service.
     */
    public function validPassword(string $password): bool
    {
        // Yes SHA1 is unsafe but in this context its fine.
        $hash   = sha1($password);
        $prefix = substr($hash, 0, 5);
        $rest   = substr($hash, 5);
        $url    = sprintf('https://api.pwnedpasswords.com/range/%s', $prefix);
        $opt    = [
            'headers' => [
                'User-Agent'  => sprintf('Firefly III v%s', config('firefly.version')),
                'Add-Padding' => 'true',
            ],
            'timeout' => 3.1415,
        ];

        app('log')->debug(sprintf('hash prefix is %s', $prefix));
        app('log')->debug(sprintf('rest is %s', $rest));

        try {
            $client = new Client();
            $res    = $client->request('GET', $url, $opt);
        } catch (GuzzleException|RequestException $e) {
            app('log')->error(sprintf('Could not verify password security: %s', $e->getMessage()));

            return true;
        }
        app('log')->debug(sprintf('Status code returned is %d', $res->getStatusCode()));
        if (404 === $res->getStatusCode()) {
            return true;
        }
        $strpos = stripos($res->getBody()->getContents(), $rest);
        if (false === $strpos) {
            app('log')->debug(sprintf('%s was not found in result body. Return true.', $rest));

            return true;
        }
        app('log')->debug(sprintf('Found %s, return FALSE.', $rest));

        return false;
    }
}
