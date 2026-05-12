<?php

/*
 * UserCountryResolver.php
 *
 * Determines which national-bank providers should be invoked, based on
 * the per-user `national_rates_country` preference plus the providers
 * registered in config('cer.national_providers').
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use FireflyIII\Models\Country;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;

final class UserCountryResolver
{
    public const string PREF_KEY = 'national_rates_country';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly NationalRateProviderRegistry $registry,
    ) {
    }

    /**
     * Country code preferred by a single user, or null if unset / unsupported.
     */
    public function forUser(User $user): ?string
    {
        $pref = Preferences::getForUser($user, self::PREF_KEY, null);
        if (null === $pref || null === $pref->data) {
            return null;
        }
        $code = strtoupper((string) $pref->data);
        if ('' === $code) {
            return null;
        }
        if (!$this->registry->hasProviderFor($code)) {
            return null;
        }

        return $code;
    }

    /**
     * Collect the distinct set of country codes that are:
     *   - chosen by at least one user, AND
     *   - backed by a registered provider.
     *
     * @return string[]
     */
    public function activeCountryCodes(): array
    {
        $codes = [];
        foreach ($this->userRepository->all() as $user) {
            $code = $this->forUser($user);
            if (null !== $code) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    /**
     * Validate a country code against the providers table AND the DB
     * (so the UI cannot silently store unknown countries).
     */
    public function isSelectable(string $countryCode): bool
    {
        $code = strtoupper($countryCode);
        if (!$this->registry->hasProviderFor($code)) {
            return false;
        }

        return Country::query()->where('code', $code)->exists();
    }
}
