<?php
/**
 * Account.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig;


use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount as AmountFacade;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class Account
 *
 * @package FireflyIII\Support\Twig
 */
class Account extends Twig_Extension
{

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->formatAmountByAccount(),
        ];

    }

    /**
     * Will return "active" when a part of the route matches the argument.
     * ie. "accounts" will match "accounts.index".
     *
     * @return Twig_SimpleFunction
     */
    protected function formatAmountByAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAmountByAccount', function (AccountModel $account, string $amount, bool $coloured = true): string {
            $currencyId = intval($account->getMeta('currency_id'));
            if ($currencyId === 0) {
                // Format using default currency:
                return AmountFacade::format($amount, $coloured);
            }
            $currency = TransactionCurrency::find($currencyId);

            return AmountFacade::formatAnything($currency, $amount, $coloured);
        }, ['is_safe' => ['html']]
        );
    }


}