<?php
/**
 * SpectreConfigurator.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Configuration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\Spectre\HaveAccounts;
use Log;

/**
 * Class SpectreConfigurator.
 */
class SpectreConfigurator implements ConfiguratorInterface
{
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /** @var string */
    private $warning = '';

    /**
     * ConfiguratorInterface constructor.
     */
    public function __construct()
    {
    }

    /**
     * Store any data from the $data array into the job.
     *
     * @param array $data
     *
     * @return bool
     * @throws FireflyException
     */
    public function configureJob(array $data): bool
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $stage = $this->getConfig()['stage'] ?? 'initial';
        Log::debug(sprintf('in getNextData(), for stage "%s".', $stage));
        switch ($stage) {
            case 'have-accounts':
                /** @var HaveAccounts $class */
                $class = app(HaveAccounts::class);
                $class->setJob($this->job);
                $class->storeConfiguration($data);

                // update job for next step and set to "configured".
                $config          = $this->getConfig();
                $config['stage'] = 'have-account-mapping';
                $this->repository->setConfiguration($this->job, $config);

                return true;
            default:
                throw new FireflyException(sprintf('Cannot store configuration when job is in state "%s"', $stage));
                break;
        }
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $config = $this->getConfig();
        $stage  = $config['stage'] ?? 'initial';

        Log::debug(sprintf('in getNextData(), for stage "%s".', $stage));
        switch ($stage) {
            case 'has-token':
                // simply redirect to Spectre.
                $config['is-redirected'] = true;
                $config['stage']         = 'user-logged-in';
                $status                  = 'configured';

                // update config and status:
                $this->repository->setConfiguration($this->job, $config);
                $this->repository->setStatus($this->job, $status);

                return $this->repository->getConfiguration($this->job);
            case 'have-accounts':
                /** @var HaveAccounts $class */
                $class = app(HaveAccounts::class);
                $class->setJob($this->job);
                $data = $class->getData();

                return $data;
            default:
                return [];
        }
    }

    /**
     * @return string
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $stage = $this->getConfig()['stage'] ?? 'initial';
        Log::debug(sprintf('in getNextView(), for stage "%s".', $stage));
        switch ($stage) {
            case 'has-token':
                // redirect to Spectre.
                Log::info('User is being redirected to Spectre.');

                return 'import.spectre.redirect';
            case 'have-accounts':
                return 'import.spectre.accounts';
            default:
                return '';

        }
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return $this->warning;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function isJobConfigured(): bool
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $stage = $this->getConfig()['stage'] ?? 'initial';
        Log::debug(sprintf('in isJobConfigured(), for stage "%s".', $stage));
        switch ($stage) {
            case 'has-token':
            case 'have-accounts':
                Log::debug('isJobConfigured returns false');

                return false;
            default:
                Log::debug('isJobConfigured returns true');

                return true;
        }
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        // make repository
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

        // set default config:
        $defaultConfig = [
            'has-token'       => false,
            'token'           => '',
            'token-expires'   => 0,
            'token-url'       => '',
            'is-redirected'   => false,
            'customer'        => null,
            'login'           => null,
            'stage'           => 'initial',
            'accounts'        => '',
            'accounts-mapped' => '',
            'auto-start'      => true,
            'apply-rules'     => true,
            'match-bills'     => false,
        ];
        $currentConfig = $this->repository->getConfiguration($job);
        $finalConfig   = array_merge($defaultConfig, $currentConfig);

        // set default extended status:
        $extendedStatus          = $this->repository->getExtendedStatus($job);
        $extendedStatus['steps'] = 6;

        // save to job:
        $job       = $this->repository->setConfiguration($job, $finalConfig);
        $job       = $this->repository->setExtendedStatus($job, $extendedStatus);
        $this->job = $job;

        return;
    }

    /**
     * Shorthand method.
     *
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }
}
