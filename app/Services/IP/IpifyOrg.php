<?php
/**
 * IpifyOrg.php
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

namespace FireflyIII\Services\IP;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;

/**
 * Class IpifyOrg
 * @codeCoverageIgnore
 */
class IpifyOrg implements IPRetrievalInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Returns the user's IP address.
     *
     * @noinspection MultipleReturnStatementsInspection
     * @return null|string
     */
    public function getIP(): ?string
    {
        try {
            $client = new Client;
            $res    = $client->request('GET', 'https://api.ipify.org');
        } catch (GuzzleException|Exception $e) {
            Log::warning(sprintf('The ipify.org service could not retrieve external IP: %s', $e->getMessage()));
            Log::warning($e->getTraceAsString());

            return null;
        }
        if (200 !== $res->getStatusCode()) {
            Log::warning(sprintf('Could not retrieve external IP: %d', $res->getStatusCode()));

            return null;
        }
        try {
            $body = (string)$res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from ipify.org result: %s', $e->getMessage()));
            $body = null;
        }

        return $body;
    }
}
