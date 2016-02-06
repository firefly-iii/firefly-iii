<?php
declare(strict_types = 1);
/**
 * AccountList.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */


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
}
