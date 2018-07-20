<?php
/**
 * ImportProvider.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Binder;

use Carbon\Carbon;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Log;

/**
 * Class ImportProvider.
 */
class ImportProvider implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return Carbon
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): string
    {
        $providers = array_keys(self::getProviders());
        if (\in_array($value, $providers, true)) {
            return $value;
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return array
     */
    public static function getProviders(): array
    {
        $repository = app(UserRepositoryInterface::class);
        // get and filter all import routines:
        /** @var User $user */
        $user = auth()->user();
        /** @var array $config */
        $providerNames = array_keys(config('import.enabled'));
        $providers     = [];
        $isDemoUser    = $repository->hasRole($user, 'demo');
        $isDebug       = (bool)config('app.debug');
        foreach ($providerNames as $providerName) {
            //Log::debug(sprintf('Now with provider %s', $providerName));
            // only consider enabled providers
            $enabled        = (bool)config(sprintf('import.enabled.%s', $providerName));
            $allowedForDemo = (bool)config(sprintf('import.allowed_for_demo.%s', $providerName));
            $allowedForUser = (bool)config(sprintf('import.allowed_for_user.%s', $providerName));
            if (false === $enabled) {
                //Log::debug('Provider is not enabled. NEXT!');
                continue;
            }

            if (true === $isDemoUser && false === $allowedForDemo) {
                //Log::debug('User is demo and this provider is not allowed for demo user. NEXT!');
                continue;
            }
            if (false === $isDemoUser && false === $allowedForUser && false === $isDebug) {
                //Log::debug('User is not demo and this provider is not allowed for such users. NEXT!');
                continue; // @codeCoverageIgnore
            }

            $providers[$providerName] = [
                'has_prereq' => (bool)config('import.has_prereq.' . $providerName),
            ];
            $class                    = (string)config(sprintf('import.prerequisites.%s', $providerName));
            $result                   = false;
            if ('' !== $class && class_exists($class)) {
                //Log::debug('Will not check prerequisites.');
                /** @var PrerequisitesInterface $object */
                $object = app($class);
                $object->setUser($user);
                $result = $object->isComplete();
            }
            $providers[$providerName]['prereq_complete'] = $result;
        }
        Log::debug(sprintf('Enabled providers: %s', json_encode(array_keys($providers))));

        return $providers;
    }
}
