<?php
/**
 * Preferences.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use Cache;
use Exception;
use FireflyIII\Models\Preference;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Log;
use Session;

/**
 * Class Preferences.
 *
 * @codeCoverageIgnore
 */
class Preferences
{
    /**
     * @param User   $user
     * @param string $search
     *
     * @return Collection
     */
    public function beginsWith(User $user, string $search): Collection
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $set = Preference::where('user_id', $user->id)->where('name', 'LIKE', $search . '%')->get();

        return $set;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function delete(string $name): bool
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $fullName = sprintf('preference%s%s', auth()->user()->id, $name);
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        try {
            Preference::where('user_id', auth()->user()->id)->where('name', $name)->delete();
        } catch (Exception $e) {
            Log::debug(sprintf('Could not delete preference: %s', $e->getMessage()));
            // don't care.
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return Collection
     */
    public function findByName(string $name): Collection
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }

        return Preference::where('name', $name)->get();
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return \FireflyIII\Models\Preference|null
     */
    public function get(string $name, $default = null): ?Preference
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s("%s") should NOT be called in the TEST environment!', __METHOD__, $name));
        }
        /** @var User $user */
        $user = auth()->user();
        if (null === $user) {
            $preference       = new Preference;
            $preference->data = $default;

            return $preference;
        }

        return $this->getForUser($user, $name, $default);
    }

    /**
     * @param \FireflyIII\User $user
     * @param array            $list
     *
     * @return array
     */
    public function getArrayForUser(User $user, array $list): array
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $result      = [];
        $preferences = Preference::where('user_id', $user->id)->whereIn('name', $list)->get(['id', 'name', 'data']);
        /** @var Preference $preference */
        foreach ($preferences as $preference) {
            $result[$preference->name] = $preference->data;
        }
        foreach ($list as $name) {
            if (!isset($result[$name])) {
                $result[$name] = null;
            }
        }

        return $result;
    }

    /**
     * @param \FireflyIII\User|Authenticatable $user
     * @param string           $name
     * @param null|string      $default
     *
     * @return \FireflyIII\Models\Preference|null
     */
    public function getForUser(User $user, string $name, $default = null): ?Preference
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s("%s") should NOT be called in the TEST environment!', __METHOD__, $name));
        }
        $fullName = sprintf('preference%s%s', $user->id, $name);
        if (Cache::has($fullName)) {
            return Cache::get($fullName);
        }

        $preference = Preference::where('user_id', $user->id)->where('name', $name)->first(['id', 'name', 'data', 'updated_at', 'created_at']);
        if (null !== $preference && null === $preference->data) {
            try {
                $preference->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete preference #%d: %s', $preference->id, $e->getMessage()));
            }
            $preference = null;
        }

        if (null !== $preference) {
            Cache::forever($fullName, $preference);

            return $preference;
        }
        // no preference found and default is null:
        if (null === $default) {
            // return NULL
            return null;
        }

        return $this->setForUser($user, $name, $default);
    }

    /**
     * @return string
     */
    public function lastActivity(): string
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $lastActivity = microtime();
        $preference   = $this->get('lastActivity', microtime());

        if (null !== $preference && null !== $preference->data) {
            $lastActivity = $preference->data;
        }
        if (is_array($lastActivity)) {
            $lastActivity = implode(',', $lastActivity);
        }

        return md5($lastActivity);
    }

    /**
     *
     */
    public function mark(): void
    {
        $this->set('lastActivity', microtime());
        Session::forget('first');
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return \FireflyIII\Models\Preference
     */
    public function set(string $name, $value): Preference
    {
        $user = auth()->user();
        if (null === $user) {
            // make new preference, return it:
            $pref       = new Preference;
            $pref->name = $name;
            $pref->data = $value;

            return $pref;
        }

        return $this->setForUser(auth()->user(), $name, $value);
    }

    /**
     * @param \FireflyIII\User $user
     * @param string           $name
     * @param mixed            $value
     *
     * @return Preference
     */
    public function setForUser(User $user, string $name, $value): Preference
    {
        $fullName = sprintf('preference%s%s', $user->id, $name);
        Cache::forget($fullName);
        /** @var Preference $pref */
        $pref = Preference::where('user_id', $user->id)->where('name', $name)->first(['id', 'name', 'data', 'updated_at', 'created_at']);

        if (null !== $pref && null === $value) {
            try {
                $pref->delete();
            } catch (Exception $e) {
                Log::error(sprintf('Could not delete preference: %s', $e->getMessage()));
            }

            return new Preference;
        }
        if (null === $value) {
            return new Preference;
        }

        if (null !== $pref) {
            $pref->data = $value;
            $pref->save();

            Cache::forever($fullName, $pref);

            return $pref;
        }

        $pref       = new Preference;
        $pref->name = $name;
        $pref->data = $value;
        $pref->user()->associate($user);

        $pref->save();

        Cache::forever($fullName, $pref);

        return $pref;
    }
}
