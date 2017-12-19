<?php
/**
 * SelectProvider.php
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

namespace FireflyIII\Support\Import\Configuration\Spectre;


use FireflyIII\Models\ImportJob;
use FireflyIII\Models\SpectreProvider;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;

/**
 * Class SelectProvider
 */
class SelectProvider implements ConfigurationInterface
{
    /** @var ImportJob */
    private $job;

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getData(): array
    {
        $config    = $this->job->configuration;
        $selection = SpectreProvider::where('country_code', $config['country'])->where('status', 'active')->get();
        $providers = [];
        /** @var SpectreProvider $provider */
        foreach ($selection as $provider) {
            $providerId             = $provider->spectre_id;
            $name                   = $provider->data['name'];
            $providers[$providerId] = $name;
        }
        $country = SelectCountry::$allCountries[$config['country']] ?? $config['country'];

        return compact('providers', 'country');
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return '';
    }

    /**
     * @param ImportJob $job
     *
     * @return void
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        $config                   = $this->job->configuration;
        $config['provider']       = intval($data['provider_code']) ?? 0; // default to fake country.
        $config['selected-provider']  = true;
        $this->job->configuration = $config;
        $this->job->save();

        return true;
    }
}