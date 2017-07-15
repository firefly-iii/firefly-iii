<?php
/**
 * CurrencyCode.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\TransactionCurrency;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CurrencyCode
 *
 * @package FireflyIII\Support\Binder
 */
class CurrencyCode implements BinderInterface
{

    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route)
    {
        $currency = TransactionCurrency::where('code', $value)->first();
        if (!is_null($currency)) {
            return $currency;
        }
        throw new NotFoundHttpException;
    }
}
