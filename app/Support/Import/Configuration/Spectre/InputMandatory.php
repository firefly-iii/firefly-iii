<?php
/**
 * InputMandatory.php
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


use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\SpectreProvider;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;

/**
 * Class InputMandatory
 */
class InputMandatory implements ConfigurationInterface
{
    /** @var ImportJob */
    private $job;

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     * @throws FireflyException
     */
    public function getData(): array
    {
        $config     = $this->job->configuration;
        $providerId = $config['provider'];
        $provider   = SpectreProvider::where('spectre_id', $providerId)->first();
        if (is_null($provider)) {
            throw new FireflyException(sprintf('Cannot find Spectre provider with ID #%d', $providerId));
        }
        $fields    = $provider->data['required_fields'] ?? [];
        $positions = [];
        // Obtain a list of columns
        foreach ($fields as $key => $row) {
            $positions[$key] = $row['position'];
        }
        array_multisort($positions, SORT_ASC, $fields);
        $country = SelectCountry::$allCountries[$config['country']] ?? $config['country'];

        return compact('provider', 'country', 'fields');
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
     * @throws FireflyException
     */
    public function storeConfiguration(array $data): bool
    {
        $config     = $this->job->configuration;
        $providerId = $config['provider'];
        $provider   = SpectreProvider::where('spectre_id', $providerId)->first();
        if (is_null($provider)) {
            throw new FireflyException(sprintf('Cannot find Spectre provider with ID #%d', $providerId));
        }
        $mandatory = [];
        $fields    = $provider->data['required_fields'] ?? [];
        foreach ($fields as $field) {
            $name             = $field['name'];
            $mandatory[$name] = Crypt::encrypt($data[$name]) ?? null;
        }

        // store in config of job:
        $config['mandatory-fields']    = $mandatory;
        $config['has-input-mandatory'] = true;
        $this->job->configuration      = $config;
        $this->job->save();

        // try to grab login for this job. See what happens?
        // fire job that creates login object. user is redirected to "wait here" page (status page). Page should
        // refresh and go back to interactive when user is supposed to enter SMS code or something.
        // otherwise start downloading stuff

        return true;
    }
}