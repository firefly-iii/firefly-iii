<?php
/**
 * AccountList.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Binder;


use FireflyIII\Models\Account;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AccountList
 *
 * @package FireflyIII\Support\Binder
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
