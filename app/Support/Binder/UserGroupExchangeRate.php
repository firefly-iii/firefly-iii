<?php

/**
 * UserGroupTransaction.php
 * Copyright (c) 2024 james@firefly-iii.org
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

use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\User;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserGroupTransaction.
 */
class UserGroupExchangeRate implements BinderInterface
{
    public static function routeBinder(string $value, Route $route): CurrencyExchangeRate
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $rate = CurrencyExchangeRate::where('id', (int) $value)
                ->where('user_group_id', $user->user_group_id)
                ->first()
            ;
            if (null !== $rate) {
                return $rate;
            }
        }

        throw new NotFoundHttpException();
    }
}
