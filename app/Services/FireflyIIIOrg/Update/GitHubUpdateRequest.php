<?php

declare(strict_types=1);

/*
 * GitHubUpdateRequest.php
 * Copyright (c) 2026 james@firefly-iii.org
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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Override;

class GitHubUpdateRequest implements UpdateRequestInterface
{
    private string $currentVersion = '1.0.0';
    private Carbon $currentBuild;
    private string $channel        = 'stable';
    private bool   $localDebug     = false;

    #[Override]
    public function getUpdateInformation(string $currentVersion, Carbon $currentBuild, string $channel): UpdateResponse
    {
        $this->currentVersion = $currentVersion;
        $this->channel        = $channel;
        $this->currentBuild   = $currentBuild;
        Log::debug(sprintf('Now in getUpdateInformation(%s, %s)', $currentVersion, $channel));

        $response             = new UpdateResponse();
        $releases             = $this->getReleases();
        $filtered             = $this->filterReleases($releases);

        Log::debug(sprintf('Left with %d release(s) to compare', count($filtered)));

        if (0 === count($filtered)) {
            $response->setNewVersionAvailable(false);

            return $response;
        }

        $newest               = $this->getNewest($filtered);

        Log::debug(sprintf('Newest release is "%s" released on %s', $newest['version'], $newest['published_at']->format('Y-m-d H:i')));

        $response->setNewVersion($newest['version']);
        $response->setPublishedAt($newest['published_at']);

        // main question, is this release newer than the current one?
        // if current version is "develop", compare date.
        if (str_contains($currentVersion, 'develop')) {
            Log::debug(sprintf('Compare with current version "%s", built on %s', $currentVersion, $currentBuild->format('Y-m-d H:i')));
            if ($currentBuild->gt($newest['published_at'])) {
                Log::debug(sprintf(
                    'Current build %s is older than newest build %s, so a new release is available.',
                    $currentBuild->format('Y-m-d H:i'),
                    $newest['published_at']->format('Y-m-d H:i')
                ));
                $response->setNewVersionAvailable(true);

                return $response;
            }
            if ($currentBuild->lt($newest['published_at'])) {
                Log::debug(sprintf(
                    'Current build %s is newer than newest build %s, so NO new release is available.',
                    $currentBuild->format('Y-m-d H:i'),
                    $newest['published_at']->format('Y-m-d H:i')
                ));
                $response->setNewVersionAvailable(false);

                return $response;
            }

            return $response;
        }

        // if not, compare version.
        $res                  = version_compare($newest['version'], $currentVersion);
        if ($res < 1) {
            $response->setNewVersionAvailable(false);

            return $response;
        }
        if (1 === $res) {
            $response->setNewVersionAvailable(true);

            return $response;
        }

        return $response;
    }

    private function filterReleases(array $releases): array
    {
        $return = [];

        /** @var array $release */
        foreach ($releases as $release) {
            if ($release['published_at']->lte($this->currentBuild)) {
                Log::debug(sprintf('Skip older version "%s"', $release['version']));

                continue;
            }
            // if channel is stable, and version is "alpha", continue.
            // if channel is stable, and version is "beta", continue.
            // if channel is beta, and version is "alpha", continue.
            if (('stable' === $this->channel || 'beta' === $this->channel) && str_contains($release['version'], 'alpha')) {
                Log::debug(sprintf('Skip version "%s"', $release['version']));

                continue;
            }
            if ('stable' === $this->channel && str_contains($release['version'], 'beta')) {
                Log::debug(sprintf('Skip version "%s"', $release['version']));

                continue;
            }
            $return[] = $release;
        }

        return $return;
    }

    private function getNewest(array $releases): array
    {
        $latest = ['version'      => '1.0.0', 'published_at' => now()->startOfYear()];
        foreach ($releases as $release) {
            Log::debug(sprintf('Comparing current version "%s" with latest version "%s"', $release['version'], $latest['version']));
            if (str_contains($release['version'], 'develop') || str_contains($latest['version'], 'develop')) {
                Log::debug('Compare based on build time.');
                // compare build times.
                if ($latest['published_at']->lt($release['published_at'])) {
                    Log::debug(sprintf(
                        'Current date "%s" is newer than latest date "%s"',
                        $release['published_at']->format('Y-m-d H:i'),
                        $latest['published_at']->format('Y-m-d H:i')
                    ));
                    $latest = $release;
                }
            }
            if (!str_contains($release['version'], 'develop') && !str_contains($latest['version'], 'develop')) {
                Log::debug('[a] Compare based on version.');
                // compare version.
                $res = version_compare($release['version'], $latest['version']);
                if (1 === $res) {
                    Log::debug(sprintf('Version "%s" is newer than version "%s".', $release['version'], $latest['version']));
                    $latest = $release;
                }
            }
        }
        Log::debug(sprintf('Latest version seems to be "%s", released at %s', $latest['version'], $latest['published_at']->format('Y-m-d H:i')));

        return $latest;
    }

    private function getReleases(): array
    {
        $client = new Client();
        $opts   = ['headers'   => ['User-Agent' => 'FireflyIII/'.config('firefly.version')]];
        $return = [];
        $body   = '';
        if ($this->localDebug && file_exists('json.json')) {
            $body = file_get_contents('json.json');
        }
        if (!$this->localDebug) {
            try {
                $res = $client->get('https://api.github.com/repos/firefly-iii/firefly-iii/releases', $opts);
            } catch (ClientException $e) {
                Log::error($e->getMessage());

                return [];
            }
            Log::debug('Successfully contacted GitHub');
            $body = (string) $res->getBody();
        }

        if (!json_validate($body)) {
            Log::debug('GitHub returned invalid JSON');

            return [];
        }
        $json   = json_decode($body, true);

        /** @var array $release */
        foreach ($json as $release) {
            $version   = $release['tag_name'];
            $version   = 'v' === $version[0] ? substr($version, 1) : $version;
            $published = Carbon::parse($release['published_at']);

            // always skip "develop" releases, unless user is also running develop.
            if (str_contains($version, 'develop') && !str_contains($this->currentVersion, 'develop')) {
                Log::debug(sprintf('Skip version "%s"', $release['tag_name']));

                continue;
            }

            $return[]  = ['version'      => $version, 'published_at' => $published];
        }

        return $return;
    }
}
