<?php

/**
 * TagList.php
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

namespace FireflyIII\Support\Binder;

use FireflyIII\Support\Facades\Preferences;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TagList.
 */
class PreferenceList implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        if (auth()->check()) {
            if ('' === $value) {
                Log::warning('Preference list count is empty, return 404.');

                throw new NotFoundHttpException();
            }

            $list   = array_unique(explode(',', $value));
            Log::debug('List of preferences is', $list);
            $result = new Collection();
            foreach ($list as $item) {
                $current = Preferences::get($item);
                if (null !== $current) {
                    Log::debug(sprintf('Add %s to the result', $item));
                    $result->push($current);
                }
            }
            Log::debug('List of preferences is', $result->toArray());

            return $result;
        }
        Log::error('PreferenceList: user is not logged in.');

        throw new NotFoundHttpException();
    }
}
