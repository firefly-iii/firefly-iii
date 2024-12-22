<?php

/**
 * AccountList.php
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

use FireflyIII\Models\AccountType;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AccountList.
 */
class AccountList implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        if (auth()->check()) {
            $collection = new Collection();
            if ('allAssetAccounts' === $value) {
                /** @var Collection $collection */
                $collection = auth()->user()->accounts()
                                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                    ->whereIn('account_types.type', [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE])
                                    ->orderBy('accounts.name', 'ASC')
                                    ->get(['accounts.*']);
            }
            if ('allAssetAccounts' !== $value) {
                $incoming = array_map('\intval', explode(',', $value));
                $list     = array_merge(array_unique($incoming), [0]);

                /** @var Collection $collection */
                $collection = auth()->user()->accounts()
                                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                    ->whereIn('accounts.id', $list)
                                    ->orderBy('accounts.name', 'ASC')
                                    ->get(['accounts.*']);
            }

            if ($collection->count() > 0) {
                return $collection;
            }
        }
        app('log')->error(sprintf('Trying to show account list (%s), but user is not logged in or list is empty.', $route->uri));

        throw new NotFoundHttpException();
    }
}
