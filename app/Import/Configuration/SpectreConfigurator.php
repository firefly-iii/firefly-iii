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

use FireflyIII\Models\ImportJob;
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
        die('cannot store config');
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('in getNextData(), user will be redirected next.');
        // update config to tell Firefly the user is redirected.
        $config                   = $this->job->configuration;
        $config['is-redirected']  = true;
        $config['stage']          = 'redirected';
        $this->job->configuration = $config;
        $this->job->status        = 'configured';
        $this->job->save();

        return $this->job->configuration;
    }

    /**
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('Send user redirect view');

        // sends the user to spectre.
        return 'import.spectre.redirect';
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
        Log::debug('in isJobConfigured');
        // job is configured (and can start) when token is empty:
        $config = $this->job->configuration;
        if ($config['has-token'] === false && $config['is-redirected'] === true) {
            Log::debug('has-token is false, is-redirected is true, return true');

            return true;
        }
        Log::debug('return false');

        return false;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $defaultConfig           = [
            'has-token'     => false,
            'token'         => '',
            'token-expires' => 0,
            'token-url'     => '',
            'is-redirected' => false,
            'customer'      => null,
            'login'         => null,
            'stage'         => 'initial',
            'accounts'      => [],
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
