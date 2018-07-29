<?php
/**
 * YnabJobConfiguration.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\JobConfiguration;

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class YnabJobConfiguration
 */
class YnabJobConfiguration implements JobConfigurationInterface
{
    /** @var ImportJob The import job */
    private $importJob;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        // config is only needed when the job is in stage "new".
        if ($this->importJob->stage === 'new') {
            Log::debug('YNAB configurationComplete: stage is new, return false');

            return false;
        }

        Log::debug('YNAB configurationComplete: stage is not new, return true');

        return true;
    }

    /**
     * Store any data from the $data array into the job. Anything in the message bag will be flashed
     * as an error to the user, regardless of its content.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('YNAB configureJob: nothing to do.');

        // there is never anything to store from this job.
        return new MessageBag;
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $data = [];
        // here we update the job so it can redirect properly to YNAB
        if ($this->importJob->stage === 'new') {

            // update stage to make sure we catch the token.
            $this->repository->setStage($this->importJob, 'catch-auth-code');
            $clientId          = (string)config('import.options.ynab.client_id');
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
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('Return YNAB redirect view.');
        return 'import.ynab.redirect';
    }

    /**
     * Set import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}