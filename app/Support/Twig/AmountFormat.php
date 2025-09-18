<?php

/**
 * AmountFormat.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Support\Facades\Log;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Override;

/**
 * Contains all amount formatting routines.
 */
class AmountFormat extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            $this->formatAmount(),
            $this->formatAmountPlain(),
        ];
    }

    protected function formatAmount(): TwigFilter
    {
        return new TwigFilter(
            'formatAmount',
            static function (string $string): string {
                $currency = Amount::getPrimaryCurrency();

                return Amount::formatAnything($currency, $string, true);
            },
            ['is_safe' => ['html']]
        );
    }

    protected function formatAmountPlain(): TwigFilter
    {
        return new TwigFilter(
            'formatAmountPlain',
            static function (string $string): string {
                $currency = Amount::getPrimaryCurrency();

                return Amount::formatAnything($currency, $string, false);
            },
            ['is_safe' => ['html']]
        );
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            $this->formatAmountByAccount(),
            $this->formatAmountBySymbol(),
            $this->formatAmountByCurrency(),
            $this->formatAmountByCode(),
        ];
    }

    /**
     * Will format the amount by the currency related to the given account.
     *
     * TODO Remove me when v2 hits.
     */
    protected function formatAmountByAccount(): TwigFunction
    {
        return new TwigFunction(
            'formatAmountByAccount',
            static function (AccountModel $account, string $amount, ?bool $coloured = null): string {
                $coloured ??= true;

                /** @var AccountRepositoryInterface $accountRepos */
                $accountRepos = app(AccountRepositoryInterface::class);
                $currency     = $accountRepos->getAccountCurrency($account) ?? Amount::getPrimaryCurrency();

                return Amount::formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Will format the amount by the currency related to the given account.
     */
    protected function formatAmountBySymbol(): TwigFunction
    {
        return new TwigFunction(
            'formatAmountBySymbol',
            static function (string $amount, ?string $symbol = null, ?int $decimalPlaces = null, ?bool $coloured = null): string {

                if (null === $symbol) {
                    $message  = sprintf('formatAmountBySymbol("%s", %s, %d, %s) was called without a symbol. Please browse to /flush to clear your cache.', $amount, var_export($symbol, true), $decimalPlaces, var_export($coloured, true));
                    Log::error($message);
                    $currency = Amount::getPrimaryCurrency();
                }
                if (null !== $symbol) {
                    $decimalPlaces ??= 2;
                    $coloured      ??= true;
                    $currency                 = new TransactionCurrency();
                    $currency->symbol         = $symbol;
                    $currency->decimal_places = $decimalPlaces;
                }

                return Amount::formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Will format the amount by the currency related to the given account.
     */
    protected function formatAmountByCurrency(): TwigFunction
    {
        return new TwigFunction(
            'formatAmountByCurrency',
            static function (TransactionCurrency $currency, string $amount, ?bool $coloured = null): string {
                $coloured ??= true;

                return Amount::formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Use the code to format a currency.
     */
    protected function formatAmountByCode(): TwigFunction
    {
        // formatAmountByCode
        return new TwigFunction(
            'formatAmountByCode',
            static function (string $amount, string $code, ?bool $coloured = null): string {
                $coloured ??= true;

                try {
                    $currency = Amount::getTransactionCurrencyByCode($code);
                } catch (FireflyException) {
                    Log::error(sprintf('Could not find currency with code "%s". Fallback to primary currency.', $code));
                    $currency = Amount::getPrimaryCurrency();
                    Log::error(sprintf('Fallback currency is "%s".', $currency->code));
                }

                return Amount::formatAnything($currency, $amount, $coloured);
            },
            ['is_safe' => ['html']]
        );
    }
}
