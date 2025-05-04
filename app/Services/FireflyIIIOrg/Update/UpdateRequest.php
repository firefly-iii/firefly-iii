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
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateRequest
 */
class UpdateRequest implements UpdateRequestInterface
{
    public function getUpdateInformation(string $channel): array
    {
        Log::debug(sprintf('Now in getUpdateInformation(%s)', $channel));
        $information = [
            'level'   => 'error',
            'message' => (string) trans('firefly.unknown_error'),
        ];

        // try to get array from update server:
        $updateInfo  = $this->contactServer($channel);
        if ('error' === $updateInfo['level']) {
            Log::error('Update information contains an error.');
            Log::error($updateInfo['message']);
            $information['message'] = $updateInfo['message'];

            return $information;
        }

        // if no error, parse the result and return
        return $this->parseResult($updateInfo);
    }

    private function contactServer(string $channel): array
    {
        Log::debug(sprintf('Now in contactServer(%s)', $channel));
        // always fall back to current version:
        $return            = [
            'version' => config('firefly.version'),
            'date'    => today(config('app.timezone'))->startOfDay(),
            'level'   => 'error',
            'message' => (string) trans('firefly.unknown_error'),
        ];

        $url               = config('firefly.update_endpoint');
        Log::debug(sprintf('Going to call %s', $url));

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
            Log::error('Ran into Guzzle error.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $return['message'] = sprintf('Guzzle: %s', strip_tags($e->getMessage()));

            return $return;
        }

        if (200 !== $res->getStatusCode()) {
            Log::error(sprintf('Response status from server is %d.', $res->getStatusCode()));
            Log::error((string) $res->getBody());
            $return['message'] = sprintf('Error: %d', $res->getStatusCode());

            return $return;
        }
        $body              = (string) $res->getBody();

        try {
            $json = \Safe\json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Body is not valid JSON');
            Log::error($body);
            $return['message'] = 'Invalid JSON :(';

            return $return;
        }

        if (!array_key_exists($channel, $json['firefly_iii'])) {
            Log::error(sprintf('No valid update channel "%s"', $channel));
            Log::error($body);
            $return['message'] = sprintf('Unknown update channel "%s" :(', $channel);
        }

        // parse response a bit. No message yet.
        $response          = $json['firefly_iii'][$channel];
        $date              = Carbon::createFromFormat('Y-m-d', $response['date']);
        if (null === $date) {
            $date = today(config('app.timezone'));
        }
        $return['version'] = $response['version'];
        $return['level']   = 'success';
        $return['date']    = $date->startOfDay();

        Log::info('Response from update server', $response);

        return $return;
    }

    /**
     * TODO make shorter
     */
    private function parseResult(array $information): array
    {
        Log::debug('Now in parseResult()', $information);
        $current  = (string) config('firefly.version');
        $latest   = (string) $information['version'];

        // strip the 'v' from the version if it's there.
        if (str_starts_with($latest, 'v')) {
            $latest = substr($latest, 1);
        }
        if (str_starts_with($current, 'develop')) {
            return $this->parseResultDevelop($current, $latest, $information);
        }

        $compare  = version_compare($latest, $current);

        Log::debug(sprintf('Current version is "%s", latest is "%s", result is: %d', $current, $latest, $compare));

        // -1: you're running a newer version:
        if (-1 === $compare) {
            return $this->runsNewerVersion($current, $latest);
        }
        // running the current version:
        if (0 === $compare) {
            return $this->runsSameVersion($current);
        }

        // a newer version is available!
        /** @var Carbon $released */
        $released = $information['date'];
        $isBeta   = $information['is_beta'] ?? false;
        $isAlpha  = $information['is_alpha'] ?? false;

        // it's new but alpha:
        if (true === $isAlpha) {
            return $this->releasedNewAlpha($current, $latest, $released);
        }

        if (true === $isBeta) {
            return $this->releasedNewBeta($current, $latest, $released);
        }

        return $this->releasedNewVersion($current, $latest, $released);
    }

    private function parseResultDevelop(string $current, string $latest, array $information): array
    {
        Log::debug(sprintf('User is running develop version "%s"', $current));
        $parts             = explode('/', $current);
        $return            = [];

        /** @var Carbon $devDate */
        $devDate           = Carbon::createFromFormat('Y-m-d', $parts[1]);

        if ($devDate->lte($information['date'])) {
            Log::debug(sprintf('This development release is older, release = %s, latest version %s = %s', $devDate->format('Y-m-d'), $latest, $information['date']->format('Y-m-d')));
            $return['level']   = 'info';
            $return['message'] = (string) trans('firefly.update_current_dev_older', ['version' => $current, 'new_version' => $latest]);

            return $return;
        }
        Log::debug(sprintf('This development release is newer, release = %s, latest version %s = %s', $devDate->format('Y-m-d'), $latest, $information['date']->format('Y-m-d')));
        $return['level']   = 'info';
        $return['message'] = (string) trans('firefly.update_current_dev_newer', ['version' => $current, 'new_version' => $latest]);

        return $return;
    }

    private function runsNewerVersion(string $current, string $latest): array
    {
        $return = [
            'level'   => 'info',
            'message' => (string) trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $latest]),
        ];
        Log::debug('User is running a newer version', $return);

        return $return;
    }

    private function runsSameVersion(string $current): array
    {
        $return = [
            'level'   => 'info',
            'message' => (string) trans('firefly.update_current_version_alert', ['version' => $current]),
        ];
        Log::debug('User is the current version.', $return);

        return $return;
    }

    private function releasedNewAlpha(string $current, string $latest, Carbon $date): array
    {
        Log::debug('New release is also a alpha!');
        $message = (string) trans(
            'firefly.update_new_version_alert',
            [
                'your_version' => $current,
                'new_version'  => $latest,
                'date'         => $date->isoFormat((string) trans('config.month_and_day_js')),
            ]
        );

        return [
            'level'   => 'success',
            'message' => sprintf('%s %s', $message, trans('firefly.update_version_alpha')),
        ];
    }

    private function releasedNewBeta(string $current, string $latest, Carbon $date): array
    {
        Log::debug('New release is also a beta!');
        $message = (string) trans(
            'firefly.update_new_version_alert',
            [
                'your_version' => $current,
                'new_version'  => $latest,
                'date'         => $date->isoFormat((string) trans('config.month_and_day_js')),
            ]
        );

        return [
            'level'   => 'success',
            'message' => sprintf('%s %s', $message, trans('firefly.update_version_beta')),
        ];
    }

    private function releasedNewVersion(string $current, string $latest, Carbon $date): array
    {
        Log::debug('New release is old enough.');
        $message = (string) trans(
            'firefly.update_new_version_alert',
            [
                'your_version' => $current,
                'new_version'  => $latest,
                'date'         => $date->isoFormat((string) trans('config.month_and_day_js')),
            ]
        );
        Log::debug('New release is here!', [$message]);
        event(new NewVersionAvailable($message));

        return [
            'level'   => 'success',
            'message' => $message,
        ];
    }
}
