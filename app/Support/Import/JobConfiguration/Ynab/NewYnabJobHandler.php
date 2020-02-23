<?php
/**
 * NewYnabJobHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Ynab;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\MessageBag;
use Log;
use RuntimeException;

/**
 * Class NewYnabJobHandler
 */
class NewYnabJobHandler implements YnabJobConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     * @throws FireflyException
     */
    public function configurationComplete(): bool
    {
        if (!$this->hasRefreshToken()) {
            Log::debug('YNAB NewYnabJobHandler configurationComplete: stage is new, no refresh token, return false');

            return false;
        }
        if ($this->hasRefreshToken() && $this->hasClientId() && $this->hasClientSecret()) {
            Log::debug('YNAB NewYnabJobHandler configurationComplete: stage is new, has a refresh token, return true');
            // need to grab access token using refresh token
            $this->getAccessToken();
            $this->repository->setStatus($this->importJob, 'ready_to_run');
            $this->repository->setStage($this->importJob, 'get_budgets');

            return true;
        }
        Log::error('YNAB NewYnabJobHandler configurationComplete: something broke, return true');

        return true;
    }

    /**
     * Store the job configuration. There is never anything to store for this stage.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('YNAB NewYnabJobHandler configureJob: nothing to do.');

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getNextData(): array
    {
        $data = [];
        // here we update the job so it can redirect properly to YNAB
        if (!$this->hasRefreshToken() && $this->hasClientSecret() && $this->hasClientId()) {
            // update stage to make sure we catch the token.
            $this->repository->setStage($this->importJob, 'catch-auth-code');
            $clientId          = app('preferences')->get('ynab_client_id')->data;
            $callBackUri       = route('import.callback.ynab');
            $uri               = sprintf(
                'https://app.youneedabudget.com/oauth/authorize?client_id=%s&redirect_uri=%s&response_type=code&state=%s', $clientId, $callBackUri,
                $this->importJob->key
            );
            $data['token-url'] = $uri;
            Log::debug(sprintf('YNAB getNextData: URI to redirect to is %s', $uri));
        }

        return $data;
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('Return YNAB redirect view.');

        return 'import.ynab.redirect';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }


    /**
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws FireflyException
     */
    private function getAccessToken(): void
    {
        $clientId     = app('preferences')->get('ynab_client_id')->data;
        $clientSecret = app('preferences')->get('ynab_client_secret')->data;
        $refreshToken = app('preferences')->get('ynab_refresh_token')->data;
        $uri          = sprintf(
            'https://app.youneedabudget.com/oauth/token?client_id=%s&client_secret=%s&grant_type=refresh_token&refresh_token=%s', $clientId, $clientSecret,
            $refreshToken
        );

        $client = new Client();
        try {
            $res = $client->request('post', $uri);
        } catch (GuzzleException $e) {
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

        // also store new refresh token:
        $refreshToken = (string)($json['refresh_token'] ?? '');
        if ('' !== $refreshToken) {
            app('preferences')->set('ynab_refresh_token', $refreshToken);
        }


        Log::debug('end of NewYnabJobHandler::getAccessToken()');
    }

    /**
     * Check if we have the client ID.
     *
     * @return bool
     */
    private function hasClientId(): bool
    {
        $clientId = app('preferences')->getForUser($this->importJob->user, 'ynab_client_id', null);
        if (null === $clientId) {
            Log::debug('user has no YNAB client ID');

            return false;
        }
        if ('' === (string)$clientId->data) {
            Log::debug('user has no YNAB client ID (empty)');

            return false;
        }
        Log::debug('user has a YNAB client ID');

        return true;
    }

    /**
     * Check if we have the client secret
     *
     * @return bool
     */
    private function hasClientSecret(): bool
    {
        $clientSecret = app('preferences')->getForUser($this->importJob->user, 'ynab_client_secret', null);
        if (null === $clientSecret) {
            Log::debug('user has no YNAB client secret');

            return false;
        }
        if ('' === (string)$clientSecret->data) {
            Log::debug('user has no YNAB client secret (empty)');

            return false;
        }
        Log::debug('user has a YNAB client secret');

        return true;
    }

    /**
     * @return bool
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function hasRefreshToken(): bool
    {
        $preference = app('preferences')->get('ynab_refresh_token');
        if (null === $preference) {
            Log::debug('user has no YNAB refresh token.');

            return false;
        }
        if ('' === (string)$preference->data) {
            Log::debug('user has no YNAB refresh token (empty).');

            return false;
        }
        Log::debug(sprintf('user has YNAB refresh token: %s', $preference->data));

        return true;
    }
}
