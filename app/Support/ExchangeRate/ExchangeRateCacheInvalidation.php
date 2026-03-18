<?php

declare(strict_types=1);

namespace FireflyIII\Support\ExchangeRate;

use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Singleton\PreferencesSingleton;

class ExchangeRateCacheInvalidation
{
    public function getVersion(UserGroup $userGroup): string
    {
        return (string) FireflyConfig::get($this->getVersionKey($userGroup), '0')?->data;
    }

    public function invalidate(UserGroup $userGroup): void
    {
        FireflyConfig::set($this->getVersionKey($userGroup), (string) microtime(true));

        foreach (GroupMembership::with('user')->where('user_group_id', $userGroup->id)->get() as $membership) {
            if (null === $membership->user) {
                continue;
            }
            Preferences::setForUser($membership->user, 'lastActivity', microtime(true));
        }

        PreferencesSingleton::getInstance()->resetPreferences();
    }

    private function getVersionKey(UserGroup $userGroup): string
    {
        return sprintf('exchange_rate_cache_version_%d', $userGroup->id);
    }
}
