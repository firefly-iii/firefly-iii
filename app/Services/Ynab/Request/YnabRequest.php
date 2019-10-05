<?php
/**
 * YnabRequest.php
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

declare(strict_types=1);

namespace FireflyIII\Services\Ynab\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;

/**
 * Class YnabRequest
 * @codeCoverageIgnore
 */
abstract class YnabRequest
{
    /** @var string */
    protected $api;

    /** @var string */
    protected $token;

    public function __construct()
    {
        $this->api = 'https://' . config('import.options.ynab.live') . '/' . config('import.options.ynab.version');
    }

    /**
     * @param string     $uri
     * @param array|null $params
     *
     * @return array
     */
    public function authenticatedGetRequest(string $uri, array $params = null): array
    {
        Log::debug(sprintf('Now in YnabRequest::authenticatedGetRequest(%s)', $uri), $params);
        $client  = new Client;
        $params  = $params ?? [];
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ];
        if (count($params) > 0) {
            $uri = $uri . '?' . http_build_query($params);
        }
        Log::debug(sprintf('Going to call YNAB on URI: %s', $uri), $options);
        try {
            $res = $client->request('get', $uri, $options);
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return [];
        }
        try {
            $content = trim($res->getBody()->getContents());
            Log::debug(sprintf('Raw body is: %s', $content));
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return [];
        }

        return json_decode($content, true) ?? [];
    }

    /**
     *
     */
    abstract public function call(): void;

    /**
     * @param string $token
     */
    public function setAccessToken(string $token): void
    {
        $this->token = $token;
    }

}
