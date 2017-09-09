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
use FireflyIII\Models\Transaction as TransactionModel;
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
            $this->formatAmountBySymbol(),
            $this->transactionAmount(),
            $this->journalAmount(),
            $this->formatDestinationAfter(),
            $this->formatDestinationBefore(),
            $this->formatSourceAfter(),
            $this->formatSourceBefore(),
            $this->formatAmountByCurrency(),
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

            $currency = app('amount')->getDefaultCurrency();

            return app('amount')->formatAnything($currency, $string, true);
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

            if ($currencyId !== 0) {
                $currency = TransactionCurrency::find($currencyId);

                return app('amount')->formatAnything($currency, $amount, $coloured);
            }
            $currency = app('amount')->getDefaultCurrency();

            return app('amount')->formatAnything($currency, $amount, $coloured);


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * Will format the amount by the currency related to the given account.
     *
     * @return Twig_SimpleFunction
     */
    protected function formatAmountByCurrency(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAmountByCurrency', function (TransactionCurrency $currency, string $amount, bool $coloured = true): string {

            return app('amount')->formatAnything($currency, $amount, $coloured);


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * Will format the amount by the currency related to the given account.
     *
     * @return Twig_SimpleFunction
     */
    protected function formatAmountBySymbol(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAmountBySymbol', function (string $amount, string $symbol, int $decimalPlaces = 2, bool $coloured = true): string {

            $currency                 = new TransactionCurrency;
            $currency->symbol         = $symbol;
            $currency->decimal_places = $decimalPlaces;

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

            $currency = app('amount')->getDefaultCurrency();

            return app('amount')->formatAnything($currency, $string, false);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function formatDestinationAfter(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatDestinationAfter', function (array $transaction): string {

            // build fake currency for main amount.
            $format                 = new TransactionCurrency;
            $format->decimal_places = $transaction['transaction_currency_dp'];
            $format->symbol         = $transaction['transaction_currency_symbol'];
            $string                 = app('amount')->formatAnything($format, $transaction['destination_account_after'], true);

            // also append foreign amount for clarity:
            if (!is_null($transaction['foreign_destination_amount'])) {
                // build fake currency for foreign amount
                $format                 = new TransactionCurrency;
                $format->decimal_places = $transaction['foreign_currency_dp'];
                $format->symbol         = $transaction['foreign_currency_symbol'];
                $string                 .= ' (' . app('amount')->formatAnything($format, $transaction['foreign_destination_amount'], true) . ')';
            }


            return $string;

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function formatDestinationBefore(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatDestinationBefore', function (array $transaction): string {

            // build fake currency for main amount.
            $format                 = new TransactionCurrency;
            $format->decimal_places = $transaction['transaction_currency_dp'];
            $format->symbol         = $transaction['transaction_currency_symbol'];

            return app('amount')->formatAnything($format, $transaction['destination_account_before'], true);

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function formatSourceAfter(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatSourceAfter', function (array $transaction): string {

            // build fake currency for main amount.
            $format                 = new TransactionCurrency;
            $format->decimal_places = $transaction['transaction_currency_dp'];
            $format->symbol         = $transaction['transaction_currency_symbol'];
            $string                 = app('amount')->formatAnything($format, $transaction['source_account_after'], true);

            // also append foreign amount for clarity:
            if (!is_null($transaction['foreign_source_amount'])) {
                // build fake currency for foreign amount
                $format                 = new TransactionCurrency;
                $format->decimal_places = $transaction['foreign_currency_dp'];
                $format->symbol         = $transaction['foreign_currency_symbol'];
                $string                 .= ' (' . app('amount')->formatAnything($format, $transaction['foreign_source_amount'], true) . ')';
            }


            return $string;


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function formatSourceBefore(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatSourceBefore', function (array $transaction): string {

            // build fake currency for main amount.
            $format                 = new TransactionCurrency;
            $format->decimal_places = $transaction['transaction_currency_dp'];
            $format->symbol         = $transaction['transaction_currency_symbol'];

            return app('amount')->formatAnything($format, $transaction['source_account_before'], true);

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function journalAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalAmount', function (TransactionJournal $journal): string {

            return app('amount')->journalAmount($journal, true);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function transactionAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionAmount', function (TransactionModel $transaction): string {

            return app('amount')->transactionAmount($transaction, true);
        }, ['is_safe' => ['html']]
        );
    }

}
