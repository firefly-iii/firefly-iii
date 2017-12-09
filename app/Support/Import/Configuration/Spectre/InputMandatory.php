<?php
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
     * @return ConfigurationInterface
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