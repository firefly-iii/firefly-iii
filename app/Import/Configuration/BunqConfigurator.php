<?php
/**
 * BunqConfigurator.php
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

namespace FireflyIII\Import\Configuration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\Bunq\HaveAccounts;
use Log;

/**
 * @deprecated
 * @codeCoverageIgnore
 * Class BunqConfigurator.
 */
class BunqConfigurator implements ConfiguratorInterface
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
     *
     * @throws FireflyException
     */
    public function configureJob(array $data): bool
    {
        if (null === $this->job) {
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
     *
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        if (null === $this->job) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $config = $this->getConfig();
        $stage  = $config['stage'] ?? 'initial';

        Log::debug(sprintf('in getNextData(), for stage "%s".', $stage));

        switch ($stage) {
            case 'have-accounts':
                /** @var HaveAccounts $class */
                $class = app(HaveAccounts::class);
                $class->setJob($this->job);

                return $class->getData();
            default:
                return [];
        }
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        if (null === $this->job) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $stage = $this->getConfig()['stage'] ?? 'initial';

        Log::debug(sprintf('getNextView: in getNextView(), for stage "%s".', $stage));
        switch ($stage) {
            case 'have-accounts':
                return 'import.bunq.accounts';
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
     *
     * @throws FireflyException
     */
    public function isJobConfigured(): bool
    {
        if (null === $this->job) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        $stage = $this->getConfig()['stage'] ?? 'initial';

        Log::debug(sprintf('in isJobConfigured(), for stage "%s".', $stage));
        switch ($stage) {
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
            'is-redirected' => false,
            'stage'         => 'initial',
            'auto-start'    => true,
            'apply-rules'   => true,
        ];
        $currentConfig = $this->repository->getConfiguration($job);
        $finalConfig   = array_merge($defaultConfig, $currentConfig);

        // set default extended status:
        $extendedStatus          = $this->repository->getExtendedStatus($job);
        $extendedStatus['steps'] = 8;

        // save to job:
        $job       = $this->repository->setConfiguration($job, $finalConfig);
        $job       = $this->repository->setExtendedStatus($job, $extendedStatus);
        $this->job = $job;

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
