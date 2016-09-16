<?php
/**
 * UnfinishedJournal.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Binder;

use Auth;
use FireflyIII\Models\TransactionJournal;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Date
 *
 * @package FireflyIII\Support\Binder
 */
class UnfinishedJournal implements BinderInterface
{


    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route): TransactionJournal
    {
        if (auth()->check()) {
            $object = TransactionJournal::where('transaction_journals.id', $value)
                                        ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                        ->where('completed', 0)
                                        ->where('user_id', Auth::user()->id)->first(['transaction_journals.*']);
            if ($object) {
                return $object;
            }
        }

        throw new NotFoundHttpException;

    }
}
