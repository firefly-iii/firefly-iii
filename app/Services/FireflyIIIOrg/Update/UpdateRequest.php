<?php

/**
 * UpdateRequest.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Services\FireflyIIIOrg\Update;

use Carbon\Carbon;
use FireflyIII\Events\NewVersionAvailable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class UpdateRequest
 */
class UpdateRequest implements UpdateRequestInterface
{
    public function getUpdateInformation(string $channel): array
    {
        app('log')->debug(sprintf('Now in getUpdateInformation(%s)', $channel));
        $information = [
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];

        // try get array from update server:
        $updateInfo = $this->contactServer($channel);
        if ('error' === $updateInfo['level']) {
            app('log')->error('Update information contains an error.');
            app('log')->error($updateInfo['message']);
            $information['message'] = $updateInfo['message'];

            return $information;
        }

        // if no error, parse the result and return
        return $this->parseResult($updateInfo);
    }

    private function contactServer(string $channel): array
    {
        app('log')->debug(sprintf('Now in contactServer(%s)', $channel));
        // always fall back to current version:
        $return = [
            'version' => config('firefly.version'),
            'date'    => today(config('app.timezone'))->startOfDay(),
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];

        $url = config('firefly.update_endpoint');
        app('log')->debug(sprintf('Going to call %s', $url));

        try {
            $client  = new Client();
            $options = [
                'headers' => [
                    'User-Agent' => sprintf('FireflyIII/%s/%s', config('firefly.version'), $channel),
                ],
                'timeout' => 3.1415,
            ];
            $res     = $client->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            app('log')->error('Ran into Guzzle error.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $return['message'] = sprintf('Guzzle: %s', strip_tags($e->getMessage()));

            return $return;
        }

        if (200 !== $res->getStatusCode()) {
            app('log')->error(sprintf('Response status from server is %d.', $res->getStatusCode()));
            app('log')->error((string)$res->getBody());
            $return['message'] = sprintf('Error: %d', $res->getStatusCode());

            return $return;
        }
        $body = (string)$res->getBody();

        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            app('log')->error('Body is not valid JSON');
            app('log')->error($body);
            $return['message'] = 'Invalid JSON :(';

            return $return;
        }

        if (!array_key_exists($channel, $json['firefly_iii'])) {
            app('log')->error(sprintf('No valid update channel "%s"', $channel));
            app('log')->error($body);
            $return['message'] = sprintf('Unknown update channel "%s" :(', $channel);
        }

        // parse response a bit. No message yet.
        $response = $json['firefly_iii'][$channel];
        $date     = Carbon::createFromFormat('Y-m-d', $response['date']);
        if (false === $date) {
            $date = today(config('app.timezone'));
        }
        $return['version'] = $response['version'];
        $return['level']   = 'success';
        $return['date']    = $date->startOfDay();

        app('log')->info('Response from update server', $response);

        return $return;
    }

    private function parseResult(array $information): array
    {
        app('log')->debug('Now in parseResult()', $information);
        $return  = [
            'level'   => 'error',
            'message' => (string)trans('firefly.unknown_error'),
        ];
        $current = config('firefly.version');
        $latest  = $information['version'];

        // strip the 'v' from the version if it's there.
        if (str_starts_with($latest, 'v')) {
            $latest = substr($latest, 1);
        }

        $compare = version_compare($latest, $current);

        app('log')->debug(sprintf('Current version is "%s", latest is "%s", result is: %d', $current, $latest, $compare));

        // -1: you're running a newer version:
        if (-1 === $compare) {
            $return['level']   = 'info';
            $return['message'] = (string)trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $latest]);
            app('log')->debug('User is running a newer version', $return);

            return $return;
        }
        // running the current version:
        if (0 === $compare) {
            $return['level']   = 'info';
            $return['message'] = (string)trans('firefly.update_current_version_alert', ['version' => $current]);
            app('log')->debug('User is the current version.', $return);

            return $return;
        }

        // a newer version is available!
        /** @var Carbon $released */
        $released     = $information['date'];
        $today        = today(config('app.timezone'))->startOfDay();
        $diff         = $today->diffInDays($released);
        $expectedDiff = config('firefly.update_minimum_age') ?? 6;
        // it's still very fresh, and user wants a stable release:
        if ($diff <= $expectedDiff) {
            $return['level']   = 'info';
            $return['message'] = (string)trans(
                'firefly.just_new_release',
                [
                    'version' => $latest,
                    'date'    => $released->isoFormat((string)trans('config.month_and_day_js')),
                    'days'    => $expectedDiff,
                ]
            );
            app('log')->debug('Release is very fresh.', $return);

            return $return;
        }

        // it's been around for a while:
        $return['level']   = 'success';
        $return['message'] = (string)trans(
            'firefly.update_new_version_alert',
            [
                'your_version' => $current,
                'new_version'  => $latest,
                'date'         => $released->isoFormat((string)trans('config.month_and_day_js')),
            ]
        );
        app('log')->debug('New release is old enough.');

        // add warning in case of alpha or beta:
        // append warning if beta or alpha.
        $isBeta = $information['is_beta'] ?? false;
        if (true === $isBeta) {
            $return['message'] = sprintf('%s %s', $return['message'], trans('firefly.update_version_beta'));
            app('log')->debug('New release is also a beta!');
        }

        $isAlpha = $information['is_alpha'] ?? false;
        if (true === $isAlpha) {
            $return['message'] = sprintf('%s %s', $return['message'], trans('firefly.update_version_alpha'));
            app('log')->debug('New release is also a alpha!');
        }
        app('log')->debug('New release is here!', $return);

        // send event, this may result in a notification.
        event(new NewVersionAvailable($return['message']));

        return $return;
    }
}
