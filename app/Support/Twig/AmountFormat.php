<?php
/**
 * AmountFormat.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig;


use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Contains all amount formatting routines.
 *
 * @package FireflyIII\Support\Twig
 */
class AmountFormat extends Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return [
            $this->formatAmount(),
            $this->formatAmountPlain(),
        ];

    }

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
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'FireflyIII\Support\Twig\AmountFormat';
    }

    /**
     *
     * @return Twig_SimpleFilter
     */
    protected function formatAmount(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmount', function (string $string): string {

            return app('amount')->format($string);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * Will format the amount by the currency related to the given account.
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
                return app('amount')->format($amount, $coloured);
            }
            $currency = TransactionCurrency::find($currencyId);

            return app('amount')->formatAnything($currency, $amount, $coloured);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmountPlain(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmountPlain', function (string $string): string {

            return app('amount')->format($string, false);
        }, ['is_safe' => ['html']]
        );
    }
}