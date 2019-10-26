<?php
/**
 * UpdateRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\FireflyIIIOrg\Update;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Log;

/**
 * Class UpdateRequest
 */
class UpdateRequest implements UpdateRequestInterface
{

    /**
     * @param string $channel
     *
     * @return array
     * @throws FireflyException
     */
    public function getVersion(string $channel): array
    {
        $uri = 'https://version.firefly-iii.org/index.json';
        Log::debug(sprintf('Going to call %s', $uri));
        try {
            $client = new Client();
            $res    = $client->request('GET', $uri);
        } catch (GuzzleException|Exception $e) {
            throw new FireflyException(sprintf('Response error from update check: %s', $e->getMessage()));
        }

        if (200 !== $res->getStatusCode()) {
            throw new FireflyException(sprintf('Returned error code %d from update check.', $res->getStatusCode()));
        }
        $body = (string)$res->getBody();
        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new FireflyException('Invalid JSON in server response.');
        }

        if (!isset($json[$channel])) {
            throw new FireflyException(sprintf('Unknown update channel "%s"', $channel));
        }

        return $json[$channel];
    }
}