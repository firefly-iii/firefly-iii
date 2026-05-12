<?php

/*
 * NationalRateProviderRegistry.php
 *
 * Resolves a NationalRateProviderInterface for a given country code,
 * based on the `cer.national_providers` config map.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use FireflyIII\Services\ExchangeRate\Providers\NationalRateProviderInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class NationalRateProviderRegistry
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @return string[] supported ISO-3166 alpha-2 country codes
     */
    public function supportedCountries(): array
    {
        $map = (array) config('cer.national_providers', []);

        return array_values(array_map('strval', array_keys($map)));
    }

    public function hasProviderFor(string $countryCode): bool
    {
        $code = strtoupper($countryCode);
        $map  = (array) config('cer.national_providers', []);

        return array_key_exists($code, $map);
    }

    /**
     * @throws InvalidArgumentException when no provider is registered for the country
     */
    public function get(string $countryCode): NationalRateProviderInterface
    {
        $code  = strtoupper($countryCode);
        $map   = (array) config('cer.national_providers', []);
        $class = $map[$code] ?? null;
        if (null === $class || !is_string($class) || !class_exists($class)) {
            throw new InvalidArgumentException(sprintf(
                'No national exchange-rate provider registered for country "%s".',
                $code,
            ));
        }

        $instance = $this->container->make($class);
        if (!$instance instanceof NationalRateProviderInterface) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" does not implement NationalRateProviderInterface.',
                $class,
            ));
        }

        return $instance;
    }
}
