<?php
/**
 * ChooseLoginHandler.php
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
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Support\Import\Information\GetSpectreCustomerTrait;
use FireflyIII\Support\Import\Information\GetSpectreTokenTrait;
use Illuminate\Support\MessageBag;
use Log;


/**
 * Class ChooseLoginHandler
 *
 */
class ChooseLoginHandler implements SpectreJobConfigurationInterface
{
    use GetSpectreCustomerTrait, GetSpectreTokenTrait;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('Now in ChooseLoginHandler::configurationComplete()');
        $config = $this->importJob->configuration;
        if (isset($config['selected-login'])) {
            Log::debug('config[selected-login] is set, return true.');

            return true;
        }
        Log::debug('config[selected-login] is not set, return false.');

        return false;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('Now in ChooseLoginHandler::configureJob()');
        $selectedLogin            = (int)($data['spectre_login_id'] ?? 0.0);
        $config                   = $this->importJob->configuration;
        $config['selected-login'] = $selectedLogin;
        $this->repository->setConfiguration($this->importJob, $config);
        Log::debug(sprintf('The selected login by the user is #%d', $selectedLogin));

        // if selected login is zero, create a new one.
        if (0 === $selectedLogin) {
            Log::debug('Login is zero, get Spectre customer + token and store it in config.');
            $customer = $this->getCustomer($this->importJob);
            // get a token for the user and redirect to next stage
            $token              = $this->getToken($this->importJob, $customer);
            $config['customer'] = $customer->toArray();
            $config['token']    = $token->toArray();
            $this->repository->setConfiguration($this->importJob, $config);
            // move job to correct stage to redirect to Spectre:
            $this->repository->setStage($this->importJob, 'do-authenticate');

            return new MessageBag;

        }
        $this->repository->setStage($this->importJob, 'authenticated');

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('Now in ChooseLoginHandler::getNextData()');
        $config = $this->importJob->configuration;
        $data   = ['logins' => []];
        $logins = $config['all-logins'] ?? [];
        Log::debug(sprintf('Count of logins in configuration is %d.', count($logins)));
        foreach ($logins as $login) {
            $data['logins'][] = new Login($login);
        }

        return $data;
    }

    /**
     * @codeCoverageIgnore
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.choose-login';
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
}
