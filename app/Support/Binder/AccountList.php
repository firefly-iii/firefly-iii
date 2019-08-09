<?php
/**
 * AccountList.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

use FireflyIII\Models\AccountType;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AccountList.
 */
class AccountList implements BinderInterface
{

    /**
     * @param string $value
     * @param Route $route
     *
     * @return Collection
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        //Log::debug(sprintf('Now in AccountList::routeBinder("%s")', $value));
        if (auth()->check()) {
            //Log::debug('User is logged in.');
            $collection = new Collection;
            if ('allAssetAccounts' === $value) {
                /** @var Collection $collection */
                $collection = auth()->user()->accounts()
                                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                    ->where('account_types.type', AccountType::ASSET)
                                    ->orderBy('accounts.name', 'ASC')
                                    ->get(['accounts.*']);
                //Log::debug(sprintf('Collection length is %d', $collection->count()));
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
                //Log::debug(sprintf('Collection length is %d', $collection->count()));
            }

            if ($collection->count() > 0) {
                return $collection;
            }
        }
        Log::error(sprintf('Trying to show account list (%s), but user is not logged in or list is empty.', $route->uri));
        throw new NotFoundHttpException;
    }
}
