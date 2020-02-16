<?php
/**
 * UpdateRequest.php
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

namespace FireflyIII\Services\FireflyIIIOrg\Update;

use Carbon\Carbon;
use Exception;
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
     */
    public function getUpdateInformation(string $channel): array
    {
        Log::debug(sprintf('Now in getUpdateInformation(%s)', $channel));
        $information = [
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];

        // try get array from update server:
        $updateInfo = $this->contactServer($channel);
        if ('error' === $updateInfo['level']) {
            Log::error('Update information contains an error.');
            Log::error($updateInfo['message']);
            $information['message'] = $updateInfo['message'];

            return $information;
        }

        // if no error, parse the result and return
        return $this->parseResult($updateInfo);
    }

    /**
     * @param array $information
     *
     * @return array
     */
    private function parseResult(array $information): array
    {
        Log::debug('Now in parseResult()', $information);
        $return  = [
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];
        $current = config('firefly.version');
        $latest  = $information['version'];
        $compare = version_compare($latest, $current);

        Log::debug(sprintf('Current version is "%s", latest is "%s", result is: %d', $current, $latest, $compare));

        // -1: you're running a newer version:
        if (-1 === $compare) {
            $return['level']   = 'info';
            $return['message'] = (string)trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $latest]);
            Log::debug('User is running a newer version', $return);

            return $return;
        }
        // running the current version:
        if (0 === $compare) {
            $return['level']   = 'info';
            $return['message'] = (string)trans('firefly.update_current_version_alert', ['version' => $current]);
            Log::debug('User is the current version.', $return);

            return $return;
        }
        // a newer version is available!
        /** @var Carbon $released */
        $released     = $information['date'];
        $today        = Carbon::today()->startOfDay();
        $diff         = $today->diffInDays($released);
        $expectedDiff = config('firefly.update_minimum_age') ?? 6;
        // it's still very fresh, and user wants a stable release:
        if ($diff <= $expectedDiff) {
            $return['level']   = 'info';
            $return['message'] = (string)trans(
                'firefly.just_new_release',
                ['version' => $latest,
                 'date'    => $released->formatLocalized((string)trans('config.month_and_day')),
                 'days'    => $expectedDiff,
                ]
            );
            Log::debug('Release is very fresh.', $return);

            return $return;
        }

        // its been around for a while:
        $return['level']   = 'success';
        $return['message'] = (string)trans(
            'firefly.update_new_version_alert',
            [
                'your_version' => $current,
                'new_version'  => $latest,
                'date'         => $released->formatLocalized(
                    (string)trans('config.month_and_day')
                )]
        );
        Log::debug('New release is old enough.');

        // add warning in case of alpha or beta:
        // append warning if beta or alpha.
        $isBeta = $information['is_beta'] ?? false;
        if (true === $isBeta) {
            $return['message'] = sprintf('%s %s', $return['message'], trans('firefly.update_version_beta'));
            Log::debug('New release is also a beta!');
        }

        $isAlpha = $information['is_alpha'] ?? false;
        if (true === $isAlpha) {
            $return['message'] = sprintf('%s %s', $return['message'], trans('firefly.update_version_alpha'));
            Log::debug('New release is also a alpha!');
        }
        Log::debug('New release is here!', $return);

        return $return;
    }

    /**
     * @param string $channel
     *
     * @return array
     */
    private function contactServer(string $channel): array
    {
        Log::debug(sprintf('Now in contactServer(%s)', $channel));
        // always fall back to current version:
        $return = [
            'version' => config('firefly.version'),
            'date'    => Carbon::today()->startOfDay(),
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];

        $uri = config('firefly.update_endpoint');
        Log::debug(sprintf('Going to call %s', $uri));
        try {
            $client  = new Client;
            $options = [
                'headers' => [
                    'User-Agent' => sprintf('FireflyIII/%s/%s', config('firefly.version'), $channel),
                ],
            ];
            $res     = $client->request('GET', $uri, $options);
        } catch (GuzzleException|Exception $e) {
            Log::error('Ran into Guzzle error.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $return['message'] = sprintf('Guzzle: %s', $e->getMessage());

            return $return;
        }

        if (200 !== $res->getStatusCode()) {
            Log::error(sprintf('Response status from server is %d.', $res->getStatusCode()));
            Log::error((string)$res->getBody());
            $return['message'] = sprintf('Error: %d', $res->getStatusCode());

            return $return;
        }
        $body = (string)$res->getBody();
        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        } catch (JsonException|Exception $e) {
            Log::error('Body is not valid JSON');
            Log::error($body);
            $return['message'] = 'Invalid JSON :(';

            return $return;
        }

        if (!isset($json['firefly_iii'][$channel])) {
            Log::error(sprintf('No valid update channel "%s"', $channel));
            Log::error($body);
            $return['message'] = sprintf('Unknown update channel "%s" :(', $channel);
        }

        // parse response a bit. No message yet.
        $response          = $json['firefly_iii'][$channel];
        $return['version'] = $response['version'];
        $return['level']   = 'success';
        $return['date']    = Carbon::createFromFormat('Y-m-d', $response['date'])->startOfDay();
        Log::info('Response from update server', $response);

        return $return;
    }
}