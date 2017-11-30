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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Account;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AccountList.
 */
class AccountList implements BinderInterface
{
    /**
     * @param $value
     * @param $route
     *
     * @return Collection
     */
    public static function routeBinder($value, $route): Collection
    {
        if (auth()->check()) {
            $ids = explode(',', $value);
            // filter ids:
            $ids = self::filterIds($ids);

            /** @var \Illuminate\Support\Collection $object */
            $object = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                             ->whereIn('accounts.id', $ids)
                             ->where('user_id', auth()->user()->id)
                             ->get(['accounts.*']);
            if ($object->count() > 0) {
                $object = $object->sortBy(
                    function (Account $account) {
                        return $account->name;
                    }
                );

                return $object;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected static function filterIds(array $ids): array
    {
        $new = [];
        foreach ($ids as $id) {
            if (intval($id) > 0) {
                $new[] = $id;
            }
        }
        $new = array_unique($new);

        return $new;
    }
}
