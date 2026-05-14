<?php

/*
 * UserCountryResolver.php
 *
 * Resolution order for the country used to pick a national-bank provider:
 *
 *   1. UserGroup.country_id    — the administration's selection (preferred).
 *   2. User preference         — `national_rates_country`, kept as a fallback
 *                                so existing single-user installs that already
 *                                set this preference keep working.
 *
 * Only countries that have a registered provider (see
 * NationalRateProviderRegistry) are accepted; everything else returns null.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use FireflyIII\Models\Country;
use FireflyIII\Models\UserGroup;
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
     * Country code preferred by a single user, after applying the
     * UserGroup → preference fallback chain. Null if nothing applies.
     */
    public function forUser(User $user): ?string
    {
        $group = $user->userGroup;
        if ($group instanceof UserGroup && null !== $group->country_id) {
            $country = $group->country()->first();
            if ($country instanceof Country && $this->registry->hasProviderFor((string) $country->code)) {
                return strtoupper((string) $country->code);
            }
        }

        $pref = Preferences::getForUser($user, self::PREF_KEY, null);
        if (null !== $pref && null !== $pref->data) {
            $code = strtoupper((string) $pref->data);
            if ('' !== $code && $this->registry->hasProviderFor($code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Country code for a UserGroup, or null when none is set / no provider.
     */
    public function forUserGroup(UserGroup $userGroup): ?string
    {
        if (null === $userGroup->country_id) {
            return null;
        }
        $country = $userGroup->country()->first();
        if (!$country instanceof Country) {
            return null;
        }
        $code = strtoupper((string) $country->code);
        if (!$this->registry->hasProviderFor($code)) {
            return null;
        }

        return $code;
    }

    /**
     * Distinct list of country codes that are currently in use and have
     * a registered provider.
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
     * Validate that a country can be assigned to an administration.
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
