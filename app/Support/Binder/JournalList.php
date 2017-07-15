<?php
/**
 * JournalList.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class JournalList
 *
 * @package FireflyIII\Support\Binder
 */
class JournalList implements BinderInterface
{

    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route): Collection
    {
        if (auth()->check()) {
            $ids = explode(',', $value);
            /** @var \Illuminate\Support\Collection $object */
            $object = TransactionJournal::whereIn('transaction_journals.id', $ids)
                                        ->where('transaction_journals.user_id', auth()->user()->id)
                                        ->get(['transaction_journals.*',]);

            if ($object->count() > 0) {
                return $object;
            }
        }
        throw new NotFoundHttpException;
    }
}
