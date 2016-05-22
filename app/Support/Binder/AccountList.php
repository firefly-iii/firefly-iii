<?php
/**
 * AccountList.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Binder;


use Auth;
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

        if (Auth::check()) {

            $ids = explode(',', $value);
            // filter ids:
            $ids = self::filterIds($ids);

            /** @var \Illuminate\Support\Collection $object */
            $object = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                             ->where('account_types.editable', 1)
                             ->whereIn('accounts.id', $ids)
                             ->where('user_id', Auth::user()->id)
                             ->get(['accounts.*']);
            if ($object->count() > 0) {
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
