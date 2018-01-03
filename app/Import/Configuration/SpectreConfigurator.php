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
use FireflyIII\Support\Import\Configuration\Spectre\HaveAccounts;
use Log;

/**
 * Class SpectreConfigurator.
 */
class SpectreConfigurator implements ConfiguratorInterface
{
    /** @var ImportJob */
    private $job;

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
     */
    public function configureJob(array $data): bool
    {
        $config = $this->job->configuration;
        $stage  = $config['stage'];
        $status = $this->job->status;
        Log::debug(sprintf('in getNextData(), for stage "%s".', $stage));
        switch ($stage) {
            case 'have-accounts':
                /** @var HaveAccounts $class */
                $class = app(HaveAccounts::class);
                $class->setJob($this->job);
                $class->storeConfiguration($data);

                // update job for next step and set to "configured".
                $config                   = $this->job->configuration;
                $config['stage']          = 'have-account-mapping';
                $this->job->configuration = $config;
                $this->job->status        = 'configured';
                $this->job->save();
                return true;
                break;
            default:
                throw new FireflyException(sprintf('Cannot store configuration when job is in state "%s"', $stage));
                break;
        }
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $config = $this->job->configuration;
        $stage  = $config['stage'];
        $status = $this->job->status;
        Log::debug(sprintf('in getNextData(), for stage "%s".', $stage));
        switch ($stage) {
            case 'has-token':
                // simply redirect to Spectre.
                $config['is-redirected'] = true;
                $config['stage']         = 'user-logged-in';
                $status                  = 'configured';
                break;
            case 'have-accounts':
                // use special class:
                /** @var HaveAccounts $class */
                $class = app(HaveAccounts::class);
                $class->setJob($this->job);
                $data = $class->getData();

                return $data;
            default:
                return [];
                break;

        }

        // update config and status:
        $this->job->configuration = $config;
        $this->job->status        = $status;
        $this->job->save();

        return $this->job->configuration;
    }

    /**
     * @return string
     */
    public function getNextView(): string
    {
        $config = $this->job->configuration;
        $stage  = $config['stage'];
        Log::debug(sprintf('in getNextView(), for stage "%s".', $stage));
        switch ($stage) {
            case 'has-token':
                // redirect to Spectre.
                return 'import.spectre.redirect';
                break;
            case 'have-accounts':
                return 'import.spectre.accounts';
                break;
            default:
                return '';
                break;

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
     */
    public function isJobConfigured(): bool
    {
        $config = $this->job->configuration;
        $stage  = $config['stage'];
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
    public function setJob(ImportJob $job)
    {
        $defaultConfig           = [
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
        ];
        $extendedStatus          = $job->extended_status;
        $extendedStatus['steps'] = 100;


        $config               = $job->configuration;
        $finalConfig          = array_merge($defaultConfig, $config);
        $job->configuration   = $finalConfig;
        $job->extended_status = $extendedStatus;
        $job->save();
        $this->job = $job;
    }
}
