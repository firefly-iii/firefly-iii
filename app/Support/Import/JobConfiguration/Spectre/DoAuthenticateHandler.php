<?php
/**
 * DoAuthenticateHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Support\Import\Information\GetSpectreCustomerTrait;
use FireflyIII\Support\Import\Information\GetSpectreTokenTrait;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class AuthenticateConfig
 *
 */
class DoAuthenticateHandler implements SpectreJobConfigurationInterface
{
    use GetSpectreTokenTrait, GetSpectreCustomerTrait;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @codeCoverageIgnore
     * Return true when this stage is complete.
     *
     * always returns false.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('DoAuthenticateHandler::configurationComplete() will always return false');

        return false;
    }

    /**
     * @codeCoverageIgnore
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('DoAuthenticateHandler::configureJob() will do nothing.');

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        Log::debug('Now in DoAuthenticateHandler::getNextData()');

        // getNextData() only makes sure the job is ready for the next stage.
        $this->repository->setStatus($this->importJob, 'ready_to_run');
        $this->repository->setStage($this->importJob, 'authenticated');

        // get token from configuration:
        $config = $this->importJob->configuration;
        $token  = isset($config['token']) ? new Token($config['token']) : null;

        if (null === $token) {
            // get a new one from Spectre:
            Log::debug('No existing token, get a new one.');
            // get a new token from Spectre.
            $customer = $this->getCustomer($this->importJob);
            $token    = $this->getToken($this->importJob, $customer);
        }

        return ['token-url' => $token->getConnectUrl()];
    }

    /**
     * @codeCoverageIgnore
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.redirect';
    }

    /**
     * @codeCoverageIgnore
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
}
