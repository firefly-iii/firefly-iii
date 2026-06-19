<?php

/*
 * helpers.php
 * Copyright (c) 2026 james@firefly-iii.org
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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Search\OperatorQuerySearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use function Safe\mb_ord;
use function Safe\preg_match;
use function Safe\preg_replace_callback;

if (!function_exists('env_default_when_empty')) {
    /**
     * @return null|mixed
     */
    function env_default_when_empty(mixed $value, bool | int | string | null $default = null): mixed
    {
        if (null === $value) {
            return $default;
        }
        if ('' === $value) {
            return $default;
        }

        return $value;
    }
}

if(!function_exists('parse_markdown')) {
    function parse_markdown(string $string): string {
        $converter = new GithubFlavoredMarkdownConverter(['allow_unsafe_links' => false, 'max_nesting_level' => 5, 'html_input' => 'escape']);

        return (string) $converter->convert($string);
    }
}

if(!function_exists('get_root_search_operator')) {
    function get_root_search_operator(string $operator): string {
        $result = OperatorQuerySearch::getRootOperator($operator);

        return str_replace('-', 'not_', $result);
    }
}

if(!function_exists('get_app_configuration')) {
    function get_app_configuration(string $name, mixed $default = null): mixed {
        try {
            return AppConfiguration::get($name, $default)?->data;
        } catch (FireflyException) {
            return null;
        }
    }
}

if(!function_exists('format_amount_by_symbol')) {
    function format_amount_by_symbol(string $amount, ?string $symbol = null, ?int $decimalPlaces = null, ?bool $coloured = null): string
    {
        return Steam::formatAmountBySymbol($amount, $symbol, $decimalPlaces, $coloured);
    }
}

if (!function_exists('account_get_meta_field')) {
    function account_get_meta_field(Account $account, string $field): string
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $result     = $repository->getMetaValue($account, $field);
        if (null === $result) {
            return '';
        }
        return $result;
    }
}


if (!function_exists('account_balance')) {
    function account_balance(\FireflyIII\Models\Account $account): string
    {
        /** @var Carbon $date */
        $date = now();

        // get the date from the current session. If it's in the future, keep `now()`.
        /** @var Carbon $session */
        $session = clone session('end', today(config('app.timezone'))->endOfMonth());
        if ($session->lt($date)) {
            $date = $session->copy();
            $date->endOfDay();
        }
        Log::debug(sprintf('twig balance: Call finalAccountBalance with date/time "%s"', $date->toIso8601String()));

        // 2025-10-08 replace finalAccountBalance with accountsBalancesOptimized.
        $info = Steam::accountsBalancesOptimized(new Collection()->push($account), $date)[$account->id];
        // $info             = Steam::finalAccountBalance($account, $date);
        $currency         = Steam::getAccountCurrency($account);
        $primary          = Amount::getPrimaryCurrency();
        $convertToPrimary = Amount::convertToPrimary();
        $usePrimary       = $convertToPrimary && $primary->id !== $currency->id;
        $currency         ??= $primary;
        $strings          = [];
        foreach ($info as $key => $balance) {
            if ('balance' === $key) {
                // balance in account currency.
                if (!$usePrimary) {
                    $strings[] = Amount::formatAnything($currency, $balance, false);
                }

                continue;
            }
            if ('pc_balance' === $key) {
                // balance in primary currency.
                if ($usePrimary) {
                    $strings[] = Amount::formatAnything($primary, $balance, false);
                }

                continue;
            }
            // for multi currency accounts.
            if ($usePrimary && $key !== $primary->code) {
                $strings[] = Amount::formatAnything(Amount::getTransactionCurrencyByCode($key), $balance, false);
            }
        }

        return implode(', ', $strings);

    }
}

if (!function_exists('string_is_equal')) {
    function string_is_equal(string $left, string $right): bool
    {
        return $left === $right;
    }
}

if (!function_exists('blade_escape_js')) {
    function blade_escape_js(string $string): string
    {
        // escape all non-alphanumeric characters
        // into their \x or \uHHHH representations
        if (0 === preg_match('//u', $string)) {
            throw new FireflyException('The string to escape is not a valid UTF-8 string.');
        }

        return preg_replace_callback(
            '#[^a-zA-Z0-9,\._]#Su',
            static function ($matches) {
                $char = $matches[0];

                /*
                 * A few characters have short escape sequences in JSON and JavaScript.
                 * Escape sequences supported only by JavaScript, not JSON, are omitted.
                 * \" is also supported but omitted, because the resulting string is not HTML safe.
                 */
                $short = match ($char) {
                    '\\'    => '\\\\',
                    '/'     => '\/',
                    "\x08"  => '\b',
                    "\x0C"  => '\f',
                    "\x0A"  => '\n',
                    "\x0D"  => '\r',
                    "\x09"  => '\t',
                    default => false
                };

                if ($short) {
                    return $short;
                }

                $codepoint = mb_ord($char, 'UTF-8');
                if (0x10_000 > $codepoint) {
                    return \sprintf('\u%04X', $codepoint);
                }

                // Split characters outside the BMP into surrogate pairs
                // https://tools.ietf.org/html/rfc2781.html#section-2.1
                $u    = $codepoint - 0x10_000;
                $high = 0xD800 | ($u >> 10);
                $low  = 0xDC00 | ($u & 0x3FF);

                return \sprintf('\u%04X\u%04X', $high, $low);
            },
            $string
        );
    }
}
