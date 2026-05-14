<?php

/*
 * NationalRateProviderRegistry.php
 *
 * Resolves a NationalRateProviderInterface for a given country.
 *
 * Resolution order:
 *   1. countries.provider_class (DB) — preferred, edited via seeders/admin.
 *   2. config('cer.national_providers') — fallback for tests / fresh installs
 *      where the DB column has not been seeded yet.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use FireflyIII\Models\Country;
use FireflyIII\Services\ExchangeRate\Providers\NationalRateProviderInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class NationalRateProviderRegistry
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @return string[] uppercase ISO-3166 alpha-2 country codes
     */
    public function supportedCountries(): array
    {
        $fromDb     = Country::query()
            ->withProvider()
            ->pluck('code')
            ->map(static fn ($code): string => strtoupper((string) $code))
            ->all();
        $fromConfig = array_map('strtoupper', array_keys((array) config('cer.national_providers', [])));

        return array_values(array_unique(array_merge($fromDb, $fromConfig)));
    }

    public function hasProviderFor(string $countryCode): bool
    {
        $code = strtoupper($countryCode);
        if (in_array($code, $this->supportedCountries(), true)) {
            return true;
        }

        return false;
    }

    /**
     * @throws InvalidArgumentException when no provider is registered for the country
     */
    public function get(string $countryCode): NationalRateProviderInterface
    {
        $code  = strtoupper($countryCode);
        $class = $this->resolveClass($code);
        if (null === $class || !class_exists($class)) {
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

    private function resolveClass(string $countryCode): ?string
    {
        // 1. DB
        $country = Country::query()->where('code', $countryCode)->first();
        if ($country instanceof Country && '' !== (string) $country->provider_class) {
            return (string) $country->provider_class;
        }

        // 2. config fallback
        $map   = (array) config('cer.national_providers', []);
        $class = $map[$countryCode] ?? null;

        return is_string($class) ? $class : null;
    }
}
