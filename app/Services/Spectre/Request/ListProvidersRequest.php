<?php
/**
 * ListUserRequest.php
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

namespace FireflyIII\Services\Spectre\Request;

use Log;

/**
 * Class ListUserRequest.
 */
class ListProvidersRequest extends SpectreRequest
{
    /**
     * @var array
     */
    protected $providers = [];

    /**
     *
     * @throws \Exception
     */
    public function call(): void
    {
        $hasNextPage = true;
        $nextId      = 0;
        while ($hasNextPage) {
            Log::debug(sprintf('Now calling for next_id %d', $nextId));
            $parameters = ['include_fake_providers' => 'true', 'include_provider_fields' => 'true', 'from_id' => $nextId];
            $uri        = '/api/v3/providers?' . http_build_query($parameters);
            $response   = $this->sendSignedSpectreGet($uri, []);

            // count entries:
            Log::debug(sprintf('Found %d entries in data-array', count($response['data'])));

            // extract next ID
            $hasNextPage = false;
            if (isset($response['meta']['next_id']) && intval($response['meta']['next_id']) > $nextId) {
                $hasNextPage = true;
                $nextId      = $response['meta']['next_id'];
                Log::debug(sprintf('Next ID is now %d.', $nextId));
            } else {
                Log::debug('No next page.');
            }

            // store providers:
            foreach ($response['data'] as $providerArray) {
                $providerId                   = $providerArray['id'];
                $this->providers[$providerId] = $providerArray;
                Log::debug(sprintf('Stored provider #%d', $providerId));
            }
        }

        return;
    }

    /**
     * @return array
     */
    public function getProviders(): array
    {
        return $this->providers;
    }


}
