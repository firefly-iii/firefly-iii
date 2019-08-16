<?php
/**
 * AmountFormat.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Contains all amount formatting routines.
 */
class AmountFormat extends Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            $this->formatAmount(),
            $this->formatAmountPlain(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->formatAmountByAccount(),
            $this->formatAmountBySymbol(),
            $this->formatAmountByCurrency(),
        ];
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmount(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmount',
            function (string $string): string {
                $currency = app('amount')->getDefaultCurrency();

                return app('amount')->formatAnything($currency, $string, true);
            },
            ['is_safe' => ['html']]
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
            'formatAmountByAccount',
            static function (AccountModel $account, string $amount, bool $coloured = null): string {
                $coloured = $coloured ?? true;
                /** @var AccountRepositoryInterface $accountRepos */
                $accountRepos = app(AccountRepositoryInterface::class);
                $currency     = $accountRepos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();

                return app('amount')->formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
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
            'formatAmountByCurrency',
            function (TransactionCurrency $currency, string $amount, bool $coloured = null): string {
                $coloured = $coloured ?? true;

                return app('amount')->formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
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
            'formatAmountBySymbol',

            static function (string $amount, string $symbol, int $decimalPlaces = null, bool $coloured = null): string {
                $decimalPlaces            = $decimalPlaces ?? 2;
                $coloured                 = $coloured ?? true;
                $currency                 = new TransactionCurrency;
                $currency->symbol         = $symbol;
                $currency->decimal_places = $decimalPlaces;

                return app('amount')->formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmountPlain(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmountPlain',
            function (string $string): string {
                $currency = app('amount')->getDefaultCurrency();

                return app('amount')->formatAnything($currency, $string, false);
            },
            ['is_safe' => ['html']]
        );
    }
}
