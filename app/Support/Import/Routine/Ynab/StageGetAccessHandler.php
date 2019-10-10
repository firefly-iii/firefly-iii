<?php
/**
 * StageGetAccessHandler.php
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

namespace FireflyIII\Support\Import\Routine\Ynab;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;

/**
 * Class StageGetAccessHandler
 */
class StageGetAccessHandler
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Send a token request to YNAB. Return with access token (if all goes well).
     *
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws FireflyException
     */
    public function run(): void
    {
        $config       = $this->repository->getConfiguration($this->importJob);
        $clientId     = app('preferences')->get('ynab_client_id', '')->data;
        $clientSecret = app('preferences')->get('ynab_client_secret', '')->data;
        $redirectUri  = route('import.callback.ynab');
        $code         = $config['auth_code'];
        $uri          = sprintf(
            'https://app.youneedabudget.com/oauth/token?client_id=%s&client_secret=%s&redirect_uri=%s&grant_type=authorization_code&code=%s', $clientId,
            $clientSecret, $redirectUri, $code
        );
        $client       = new Client;
        try {
            $res = $client->request('POST', $uri);
        } catch (GuzzleException|Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException($e->getMessage());
        }
        $statusCode = $res->getStatusCode();
        try {
            $content = trim($res->getBody()->getContents());
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException($e->getMessage());
        }

        $json = json_decode($content, true) ?? [];
        Log::debug(sprintf('Status code from YNAB is %d', $statusCode));
        Log::debug(sprintf('Body of result is %s', $content), $json);

        // store refresh token (if present?) as preference
        // store token in job:
        $configuration                         = $this->repository->getConfiguration($this->importJob);
        $configuration['access_token']         = $json['access_token'];
        $configuration['access_token_expires'] = (int)$json['created_at'] + (int)$json['expires_in'];
        $this->repository->setConfiguration($this->importJob, $configuration);

        Log::debug('end of StageGetAccessHandler::run()');

        $refreshToken = (string)($json['refresh_token'] ?? '');
        if ('' !== $refreshToken) {
            app('preferences')->set('ynab_refresh_token', $refreshToken);
        }
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}
