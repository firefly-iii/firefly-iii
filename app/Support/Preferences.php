<?php

/**
 * Preferences.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Preference;
use FireflyIII\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Class Preferences.
 */
class Preferences
{
    public function all(): Collection
    {
        $user = auth()->user();
        if (null === $user) {
            return new Collection();
        }

        return Preference::where('user_id', $user->id)
                         ->where('name', '!=', 'currencyPreference')
                         ->where(function (Builder $q) use ($user): void {
                             $q->whereNull('user_group_id');
                             $q->orWhere('user_group_id', $user->user_group_id);
                         })
                         ->get();
    }

    public function get(string $name, null | array | bool | int | string $default = null): ?Preference
    {
        /** @var null|User $user */
        $user = auth()->user();
        if (null === $user) {
            $preference       = new Preference();
            $preference->data = $default;

            return $preference;
        }

        return $this->getForUser($user, $name, $default);
    }

    public function getForUser(User $user, string $name, null | array | bool | int | string $default = null): ?Preference
    {
        Log::debug(sprintf('getForUser(#%d, "%s")', $user->id, $name));
        // don't care about user group ID, except for some specific preferences.
        $userGroupId = $this->getUserGroupId($user, $name);
        $query       = Preference::where('user_id', $user->id)->where('name', $name);
        if (null !== $userGroupId) {
            Log::debug('Include user group ID in query');
            $query->where('user_group_id', $userGroupId);
        }

        $preference = $query->first(['id', 'user_id', 'user_group_id', 'name', 'data', 'updated_at', 'created_at']);

        if (null !== $preference && null === $preference->data) {
            $preference->delete();
            $preference = null;
            Log::debug('Removed empty preference.');
        }

        if (null !== $preference) {
            Log::debug(sprintf('Found preference #%d for user #%d: %s', $preference->id, $user->id, $name));
            return $preference;
        }
        // no preference found and default is null:
        if (null === $default) {
            Log::debug('Return NULL, create no preference.');
            // return NULL
            return null;
        }

        return $this->setForUser($user, $name, $default);
    }

    private function getUserGroupId(User $user, string $preferenceName): ?int
    {
        $groupId = null;
        $items   = config('firefly.admin_specific_prefs') ?? [];
        if (in_array($preferenceName, $items, true)) {
            $groupId = (int) $user->user_group_id;
        }

        return $groupId;
    }

    public function delete(string $name): bool
    {
        $fullName = sprintf('preference%s%s', auth()->user()->id, $name);
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        Preference::where('user_id', auth()->user()->id)->where('name', $name)->delete();

        return true;
    }

    public function forget(User $user, string $name): void
    {
        $key = sprintf('preference%s%s', $user->id, $name);
        Cache::forget($key);
        Cache::put($key, '', 5);
    }

    public function setForUser(User $user, string $name, null | array | bool | int | string $value): Preference
    {
        $fullName = sprintf('preference%s%s', $user->id, $name);
        $userGroupId  = $this->getUserGroupId($user, $name);
        $userGroupId  = 0 === (int) $userGroupId ? null : (int) $userGroupId;

        Cache::forget($fullName);

        $query       = Preference::where('user_id', $user->id)->where('name', $name);
        if (null !== $userGroupId) {
            Log::debug('Include user group ID in query');
            $query->where('user_group_id', $userGroupId);
        }

        $preference = $query->first(['id', 'user_id', 'user_group_id', 'name', 'data', 'updated_at', 'created_at']);

        if (null !== $preference && null === $value) {
            $preference->delete();

            return new Preference();
        }
        if (null === $value) {
            return new Preference();
        }
        if (null === $preference) {
            $preference                = new Preference();
            $preference->user_id       = (int) $user->id;
            $preference->user_group_id = $userGroupId;
            $preference->name          = $name;

        }
        $preference->data = $value;
        $preference->save();
        Cache::forever($fullName, $preference);

        return $preference;
    }

    public function beginsWith(User $user, string $search): Collection
    {
        $value = sprintf('%s%%', $search);

        return Preference::where('user_id', $user->id)->whereLike('name', $value)->get();
    }

    /**
     * Find by name, has no user ID in it, because the method is called from an unauthenticated route any way.
     */
    public function findByName(string $name): Collection
    {
        return Preference::where('name', $name)->get();
    }

    public function getArrayForUser(User $user, array $list): array
    {
        $result      = [];
        $preferences = Preference::where('user_id', $user->id)
                                 ->where(function (Builder $q) use ($user): void {
                                     $q->whereNull('user_group_id');
                                     $q->orWhere('user_group_id', $user->user_group_id);
                                 })
                                 ->whereIn('name', $list)
                                 ->get(['id', 'name', 'data']);

        /** @var Preference $preference */
        foreach ($preferences as $preference) {
            $result[$preference->name] = $preference->data;
        }
        foreach ($list as $name) {
            if (!array_key_exists($name, $result)) {
                $result[$name] = null;
            }
        }

        return $result;
    }

    public function getEncrypted(string $name, mixed $default = null): ?Preference
    {
        $result = $this->get($name, $default);
        if (null === $result) {
            return null;
        }
        if ('' === $result->data) {
            // Log::warning(sprintf('Empty encrypted preference found: "%s"', $name));

            return $result;
        }

        try {
            $result->data = decrypt($result->data);
        } catch (DecryptException $e) {
            if ('The MAC is invalid.' === $e->getMessage()) {
                Log::debug('Set data to NULL');
                $result->data = null;
            }
            // Log::error(sprintf('Could not decrypt preference "%s": %s', $name, $e->getMessage()));

            return $result;
        }

        return $result;
    }

    public function getEncryptedForUser(User $user, string $name, null | array | bool | int | string $default = null): ?Preference
    {
        $result = $this->getForUser($user, $name, $default);
        if ('' === $result->data) {
            // Log::warning(sprintf('Empty encrypted preference found: "%s"', $name));

            return $result;
        }

        try {
            $result->data = decrypt($result->data);
        } catch (DecryptException $e) {
            if ('The MAC is invalid.' === $e->getMessage()) {
                Log::debug('Set data to NULL');
                $result->data = null;
            }
            // Log::error(sprintf('Could not decrypt preference "%s": %s', $name, $e->getMessage()));

            return $result;
        }


        return $result;
    }

    public function getFresh(string $name, null | array | bool | int | string $default = null): ?Preference
    {
        /** @var null|User $user */
        $user = auth()->user();
        if (null === $user) {
            $preference       = new Preference();
            $preference->data = $default;

            return $preference;
        }

        return $this->getForUser($user, $name, $default);
    }

    /**
     * @throws FireflyException
     */
    public function lastActivity(): string
    {
        $lastActivity = microtime();
        $preference   = $this->get('lastActivity', microtime());

        if (null !== $preference && null !== $preference->data) {
            $lastActivity = $preference->data;
        }
        if (is_array($lastActivity)) {
            $lastActivity = implode(',', $lastActivity);
        }

        return hash('sha256', (string) $lastActivity);
    }

    public function mark(): void
    {
        $this->set('lastActivity', microtime());
        Session::forget('first');
    }

    public function set(string $name, null | array | bool | int | string $value): Preference
    {
        /** @var null|User $user */
        $user = auth()->user();
        if (null === $user) {
            // make new preference, return it:
            $pref       = new Preference();
            $pref->name = $name;
            $pref->data = $value;

            return $pref;
        }

        return $this->setForUser($user, $name, $value);
    }

    public function setEncrypted(string $name, mixed $value): Preference
    {
        try {
            $encrypted = encrypt($value);
        } catch (EncryptException $e) {
            Log::error(sprintf('Could not encrypt preference "%s": %s', $name, $e->getMessage()));

            throw new FireflyException(sprintf('Could not encrypt preference "%s". Cowardly refuse to continue.', $name));
        }

        return $this->set($name, $encrypted);
    }
}
