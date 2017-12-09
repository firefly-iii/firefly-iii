<?php
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
        $config                   = $this->job->configuration;
        $config['provider']       = intval($data['provider_code']) ?? 0; // default to fake country.
        $config['selected-provider']  = true;
        $this->job->configuration = $config;
        $this->job->save();

        return true;
    }
}