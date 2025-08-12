<?php

/**
 * Steam.php
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

namespace FireflyIII\Support;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Singleton\PreferencesSingleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ValueError;

use function Safe\parse_url;
use function Safe\preg_replace;

/**
 * Class Steam.
 */
class Steam
{
    /**
     * https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers
     */
    public function bcround(?string $number, int $precision = 0): string
    {
        if (null === $number) {
            return '0';
        }
        if ('' === trim($number)) {
            return '0';
        }
        // if the number contains "E", it's in scientific notation, so we need to convert it to a normal number first.
        if (false !== stripos($number, 'e')) {
            $number = sprintf('%.12f', $number);
        }

        // Log::debug(sprintf('Trying bcround("%s",%d)', $number, $precision));
        if (str_contains($number, '.')) {
            if ('-' !== $number[0]) {
                return bcadd($number, '0.'.str_repeat('0', $precision).'5', $precision);
            }

            return bcsub($number, '0.'.str_repeat('0', $precision).'5', $precision);
        }

        return $number;
    }

    public function filterAccountBalances(array $total, Account $account, bool $convertToPrimary, ?TransactionCurrency $currency = null): array
    {
        Log::debug(sprintf('filterAccountBalances(#%d)', $account->id));
        $return = [];
        foreach ($total as $key => $value) {
            $return[$key] = $this->filterAccountBalance($value, $account, $convertToPrimary, $currency);
        }
        Log::debug(sprintf('end of filterAccountBalances(#%d)', $account->id));

        return $return;
    }

    public function filterAccountBalance(array $set, Account $account, bool $convertToPrimary, ?TransactionCurrency $currency = null): array
    {
        Log::debug(sprintf('filterAccountBalance(#%d)', $account->id), $set);
        if (0 === count($set)) {
            Log::debug(sprintf('Return empty array for account #%d', $account->id));

            return [];
        }
        $primaryCurrency = Amount::getPrimaryCurrency();
        if ($convertToPrimary) {
            if ($primaryCurrency->id === $currency?->id) {
                Log::debug(sprintf('Unset [%s] for account #%d (no longer unset "pc_balance")', $primaryCurrency->code, $account->id));
                unset($set[$primaryCurrency->code]);
            }
            // todo rethink this logic.
            if ($currency instanceof TransactionCurrency && $primaryCurrency->id !== $currency->id) {
                Log::debug(sprintf('Unset balance for account #%d', $account->id));
                unset($set['balance']);
            }

            if (!$currency instanceof TransactionCurrency) {
                Log::debug(sprintf('Unset balance for account #%d', $account->id));
                unset($set['balance']);
            }
        }

        if (!$convertToPrimary) {
            if (!$currency instanceof TransactionCurrency) {
                Log::debug(sprintf('Unset pc_balance and make primaryCurrency balance the balance for account #%d', $account->id));
                $set['balance'] = $set[$primaryCurrency->code] ?? '0';
                unset($set[$primaryCurrency->code]);
            }

            if ($currency instanceof TransactionCurrency) {
                Log::debug(sprintf('Unset [%s] + [%s] balance for account #%d', $primaryCurrency->code, $currency->code, $account->id));
                unset($set[$primaryCurrency->code], $set[$currency->code]);
            }
        }

        // put specific value first in array.
        if (array_key_exists('pc_balance', $set)) {
            $set = ['pc_balance' => $set['pc_balance']] + $set;
        }
        if (array_key_exists('balance', $set)) {
            $set = ['balance' => $set['balance']] + $set;
        }
        Log::debug(sprintf('Return #%d', $account->id), $set);

        return $set;
    }

    public function filterSpaces(string $string): string
    {
        $search = [
            "\u{0001}", // start of heading
            "\u{0002}", // start of text
            "\u{0003}", // end of text
            "\u{0004}", // end of transmission
            "\u{0005}", // enquiry
            "\u{0006}", // ACK
            "\u{0007}", // BEL
            "\u{0008}", // backspace
            "\u{000E}", // shift out
            "\u{000F}", // shift in
            "\u{0010}", // data link escape
            "\u{0011}", // DC1
            "\u{0012}", // DC2
            "\u{0013}", // DC3
            "\u{0014}", // DC4
            "\u{0015}", // NAK
            "\u{0016}", // SYN
            "\u{0017}", // ETB
            "\u{0018}", // CAN
            "\u{0019}", // EM
            "\u{001A}", // SUB
            "\u{001B}", // escape
            "\u{001C}", // file separator
            "\u{001D}", // group separator
            "\u{001E}", // record separator
            "\u{001F}", // unit separator
            "\u{007F}", // DEL
            "\u{00A0}", // non-breaking space
            "\u{1680}", // ogham space mark
            "\u{180E}", // mongolian vowel separator
            "\u{2000}", // en quad
            "\u{2001}", // em quad
            "\u{2002}", // en space
            "\u{2003}", // em space
            "\u{2004}", // three-per-em space
            "\u{2005}", // four-per-em space
            "\u{2006}", // six-per-em space
            "\u{2007}", // figure space
            "\u{2008}", // punctuation space
            "\u{2009}", // thin space
            "\u{200A}", // hair space
            "\u{200B}", // zero width space
            "\u{202F}", // narrow no-break space
            "\u{3000}", // ideographic space
            "\u{FEFF}", // zero width no -break space
            "\x20", // plain old normal space,
            ' ',
        ];

        // clear zalgo text
        $string = preg_replace('/(\pM{2})\pM+/u', '\1', $string);
        $string = preg_replace('/\s+/', '', $string);

        return str_replace($search, '', $string);
    }

    public function finalAccountBalanceInRange(Account $account, Carbon $start, Carbon $end, bool $convertToPrimary): array
    {
        // expand period.
        $start->startOfDay();
        $end->endOfDay();
        Log::debug(sprintf('finalAccountBalanceInRange(#%d, %s, %s)', $account->id, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        // set up cache
        $cache                = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('final-balance-in-range');
        $cache->addProperty($start);
        $cache->addProperty($convertToPrimary);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get();
        }

        $balances             = [];
        $formatted            = $start->format('Y-m-d');
        /*
         * To make sure the start balance is correct, we need to get the balance at the exact end of the previous day.
         * Since we just did "startOfDay" we can do subDay()->endOfDay() to get the correct moment.
         * THAT will be the start balance.
         */
        $request              = clone $start;
        $request->subDay()->endOfDay();
        Log::debug(sprintf('finalAccountBalanceInRange: Call finalAccountBalance with date/time "%s"', $request->toIso8601String()));
        $startBalance         = $this->finalAccountBalance($account, $request);
        $primaryCurrency      = Amount::getPrimaryCurrencyByUserGroup($account->user->userGroup);
        $accountCurrency      = $this->getAccountCurrency($account);
        $hasCurrency          = $accountCurrency instanceof TransactionCurrency;
        $currency             = $accountCurrency ?? $primaryCurrency;
        Log::debug(sprintf('Currency is %s', $currency->code));


        // set start balances:
        $startBalance[$currency->code] ??= '0';
        if ($hasCurrency) {
            $startBalance[$accountCurrency->code] ??= '0';
        }
        if (!$hasCurrency) {
            Log::debug(sprintf('Also set start balance in %s', $primaryCurrency->code));
            $startBalance[$primaryCurrency->code] ??= '0';
        }
        $currencies           = [
            $currency->id        => $currency,
            $primaryCurrency->id => $primaryCurrency,
        ];

        $balances[$formatted] = $startBalance;
        Log::debug('Final start balance: ', $startBalance);

        // sums up the balance changes per day.
        Log::debug(sprintf('Date >= %s and <= %s', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
        $set                  = $account->transactions()
            ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d H:i:s'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d  H:i:s'))
            ->groupBy('transaction_journals.date')
            ->groupBy('transactions.transaction_currency_id')
            ->orderBy('transaction_journals.date', 'ASC')
            ->whereNull('transaction_journals.deleted_at')
            ->get(
                [ // @phpstan-ignore-line
                    'transaction_journals.date',
                    'transactions.transaction_currency_id',
                    DB::raw('SUM(transactions.amount) AS sum_of_day'),
                ]
            )
        ;

        $currentBalance       = $startBalance;
        $converter            = new ExchangeRateConverter();


        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // get date object
            $carbon                               = new Carbon($entry->date, $entry->date_tz);
            $carbonKey                            = $carbon->format('Y-m-d');
            // make sure sum is a string:
            $sumOfDay                             = (string)($entry->sum_of_day ?? '0');
            // #10426 make sure sum is not in scientific notation.
            $sumOfDay                             = $this->floatalize($sumOfDay);

            // find currency of this entry, does not have to exist.
            $currencies[$entry->transaction_currency_id] ??= TransactionCurrency::find($entry->transaction_currency_id);

            // make sure this $entry has its own $entryCurrency
            /** @var TransactionCurrency $entryCurrency */
            $entryCurrency                        = $currencies[$entry->transaction_currency_id];

            Log::debug(sprintf('Processing transaction(s) on moment %s', $carbon->format('Y-m-d H:i:s')));

            // add amount to current balance in currency code.
            $currentBalance[$entryCurrency->code]        ??= '0';
            $currentBalance[$entryCurrency->code] = bcadd($sumOfDay, (string)$currentBalance[$entryCurrency->code]);

            // if not requested to convert to primary currency, add the amount to "balance", do nothing else.
            if (!$convertToPrimary) {
                $currentBalance['balance'] = bcadd((string)$currentBalance['balance'], $sumOfDay);
            }
            // if convert to primary currency add the converted amount to "pc_balance".
            // if there is a request to convert, convert to "pc_balance" and use "balance" for whichever amount is in the primary currency.
            if ($convertToPrimary) {
                $pcSumOfDay                   = $converter->convert($entryCurrency, $primaryCurrency, $carbon, $sumOfDay);
                $currentBalance['pc_balance'] = bcadd((string)($currentBalance['pc_balance'] ?? '0'), $pcSumOfDay);
                // if it's the same currency as the entry, also add to balance (see other code).
                if ($currency->id === $entryCurrency->id) {
                    $currentBalance['balance'] = bcadd((string)$currentBalance['balance'], $sumOfDay);
                }
            }
            // add to final array.
            $balances[$carbonKey]                 = $currentBalance;
            Log::debug(sprintf('Updated entry [%s]', $carbonKey), $currentBalance);
        }
        $cache->store($balances);
        Log::debug('End of method');

        return $balances;
    }

    public function accountsBalancesOptimized(Collection $accounts, Carbon $date, ?TransactionCurrency $primary = null, ?bool $convertToPrimary = null): array
    {
        Log::debug(sprintf('accountsBalancesOptimized: Called for %d account(s) with date/time "%s"', $accounts->count(), $date->toIso8601String()));
        $result      = [];
        $convertToPrimary ??= Amount::convertToPrimary();
        $primary          ??= Amount::getPrimaryCurrency();
        $currencies  = $this->getCurrencies($accounts);

        // balance(s) in all currencies for ALL accounts.
        $arrayOfSums = Transaction::whereIn('account_id', $accounts->pluck('id')->toArray())
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transactions.transaction_currency_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
            ->groupBy(['transactions.account_id', 'transaction_currencies.code'])
            ->get(['transactions.account_id', 'transaction_currencies.code', DB::raw('SUM(transactions.amount) as sum_of_amount')])->toArray()
        ;

        /** @var Account $account */
        foreach ($accounts as $account) {
            // this array is PER account, so we wait a bit before we change code here.
            $return               = [
                'pc_balance' => '0',
                'balance'    => '0', // this key is overwritten right away, but I must remember it is always created.
            ];
            $currency             = $currencies[$account->id];

            // second array
            $accountSum           = array_filter($arrayOfSums, function ($entry) use ($account) {
                return $entry['account_id'] === $account->id;
            });
            if (0 === count($accountSum)) {
                $result[$account->id] = $return;

                continue;
            }
            $accountSum           = array_values($accountSum)[0];
            $sumsByCode           = [
                $accountSum['code'] => $accountSum['sum_of_amount'],
            ];

            // Log::debug('All balances are (joined)', $others);
            // if there is no request to convert, take this as "balance" and "pc_balance".
            $return['balance']    = $sumsByCode[$currency->code] ?? '0';
            if (!$convertToPrimary) {
                unset($return['pc_balance']);
                // Log::debug(sprintf('Set balance to %s, unset pc_balance', $return['balance']));
            }
            // if there is a request to convert, convert to "pc_balance" and use "balance" for whichever amount is in the primary currency.
            if ($convertToPrimary) {
                $return['pc_balance'] = $this->convertAllBalances($sumsByCode, $primary, $date);
                // Log::debug(sprintf('Set pc_balance to %s', $return['pc_balance']));
            }

            // either way, the balance is always combined with the virtual balance:
            $virtualBalance       = (string)('' === (string)$account->virtual_balance ? '0' : $account->virtual_balance);

            if ($convertToPrimary) {
                // the primary currency balance is combined with a converted virtual_balance:
                $converter            = new ExchangeRateConverter();
                $pcVirtualBalance     = $converter->convert($currency, $primary, $date, $virtualBalance);
                $return['pc_balance'] = bcadd($pcVirtualBalance, $return['pc_balance']);
                // Log::debug(sprintf('Primary virtual balance makes the primary total %s', $return['pc_balance']));
            }
            if (!$convertToPrimary) {
                // if not, also increase the balance + primary balance for consistency.
                $return['balance'] = bcadd($return['balance'], $virtualBalance);
                // Log::debug(sprintf('Virtual balance makes the (primary currency) total %s', $return['balance']));
            }
            $final                = array_merge($return, $sumsByCode);
            $result[$account->id] = $final;
            // Log::debug('Final balance is', $final);
        }

        return $result;
    }

    /**
     * Returns smaller than or equal to, so be careful with END OF DAY.
     *
     * Returns the balance of an account at exact moment given. Array with at least one value.
     * Always returns:
     * "balance": balance in the account's currency OR user's primary currency if the account has no currency
     * "EUR": balance in EUR (or whatever currencies the account has balance in)
     *
     * If the user has $convertToPrimary:
     * "balance": balance in the account's currency OR user's primary currency if the account has no currency
     * --> "pc_balance": balance in the user's primary currency, with all amounts converted to the primary currency.
     * "EUR": balance in EUR (or whatever currencies the account has balance in)
     */
    public function finalAccountBalance(Account $account, Carbon $date, ?TransactionCurrency $primary = null, ?bool $convertToPrimary = null): array
    {

        $cache             = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty($date);
        if ($cache->has()) {
            Log::debug(sprintf('CACHED finalAccountBalance(#%d, %s)', $account->id, $date->format('Y-m-d H:i:s')));

            // return $cache->get();
        }
        // Log::debug(sprintf('finalAccountBalance(#%d, %s)', $account->id, $date->format('Y-m-d H:i:s')));
        if (null === $convertToPrimary) {
            $convertToPrimary = Amount::convertToPrimary($account->user);
        }
        if (!$primary instanceof TransactionCurrency) {
            $primary = Amount::getPrimaryCurrencyByUserGroup($account->user->userGroup);
        }
        // account balance thing.
        $currencyPresent   = isset($account->meta) && array_key_exists('currency', $account->meta) && null !== $account->meta['currency'];
        if ($currencyPresent) {
            $accountCurrency = $account->meta['currency'];
        }
        if (!$currencyPresent) {

            $accountCurrency = $this->getAccountCurrency($account);
        }
        $hasCurrency       = null !== $accountCurrency;
        $currency          = $hasCurrency ? $accountCurrency : $primary;
        $return            = [
            'pc_balance' => '0',
            'balance'    => '0', // this key is overwritten right away, but I must remember it is always created.
        ];
        // balance(s) in all currencies.
        $array             = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transactions.transaction_currency_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
            ->get(['transaction_currencies.code', 'transactions.amount'])->toArray()
        ;
        $others            = $this->groupAndSumTransactions($array, 'code', 'amount');
        // Log::debug('All balances are (joined)', $others);
        // if there is no request to convert, take this as "balance" and "pc_balance".
        $return['balance'] = $others[$currency->code] ?? '0';
        if (!$convertToPrimary) {
            unset($return['pc_balance']);
            // Log::debug(sprintf('Set balance to %s, unset pc_balance', $return['balance']));
        }
        // if there is a request to convert, convert to "pc_balance" and use "balance" for whichever amount is in the primary currency.
        if ($convertToPrimary) {
            $return['pc_balance'] = $this->convertAllBalances($others, $primary, $date); // todo sum all and convert.
            // Log::debug(sprintf('Set pc_balance to %s', $return['pc_balance']));
        }

        // either way, the balance is always combined with the virtual balance:
        $virtualBalance    = (string)('' === (string)$account->virtual_balance ? '0' : $account->virtual_balance);

        if ($convertToPrimary) {
            // the primary currency balance is combined with a converted virtual_balance:
            $converter            = new ExchangeRateConverter();
            $pcVirtualBalance     = $converter->convert($currency, $primary, $date, $virtualBalance);
            $return['pc_balance'] = bcadd($pcVirtualBalance, $return['pc_balance']);
            // Log::debug(sprintf('Primary virtual balance makes the primary total %s', $return['pc_balance']));
        }
        if (!$convertToPrimary) {
            // if not, also increase the balance + primary balance for consistency.
            $return['balance'] = bcadd($return['balance'], $virtualBalance);
            // Log::debug(sprintf('Virtual balance makes the (primary currency) total %s', $return['balance']));
        }
        $final             = array_merge($return, $others);
        // Log::debug('Final balance is', $final);
        $cache->store($final);

        return $final;
    }

    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $type   = $account->accountType->type;
        $list   = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $result = $account->accountMeta()->where('name', 'currency_id')->first();
        if (null === $result) {
            return null;
        }

        return TransactionCurrency::find((int)$result->data);
    }

    private function groupAndSumTransactions(array $array, string $group, string $field): array
    {
        $return = [];

        foreach ($array as $item) {
            $groupKey          = $item[$group] ?? 'unknown';
            $return[$groupKey] = bcadd($return[$groupKey] ?? '0', (string)$item[$field]);
        }

        return $return;
    }

    private function convertAllBalances(array $others, TransactionCurrency $primary, Carbon $date): string
    {
        $total     = '0';
        $converter = new ExchangeRateConverter();
        $singleton = PreferencesSingleton::getInstance();
        foreach ($others as $key => $amount) {
            $preference = $singleton->getPreference($key);
            $currency   = $preference ?? TransactionCurrency::where('code', $key)->first();
            if (null === $currency) {
                continue;
            }
            if (null === $preference) {
                $singleton->setPreference($key, $currency);
            }
            $current    = $amount;
            if ($currency->id !== $primary->id) {
                $current = $converter->convert($currency, $primary, $date, $amount);
                Log::debug(sprintf('Convert %s %s to %s %s', $currency->code, $amount, $primary->code, $current));
            }
            $total      = bcadd($current, $total);
        }

        return $total;
    }

    /**
     * @throws FireflyException
     */
    public function getHostName(string $ipAddress): string
    {
        $host = '';

        try {
            $hostName = gethostbyaddr($ipAddress);
        } catch (Exception $e) {
            app('log')->error($e->getMessage());
            $hostName = $ipAddress;
        }

        if ('' !== (string)$hostName && $hostName !== $ipAddress) {
            $host = $hostName;
        }

        return (string)$host;
    }

    public function getLastActivities(array $accounts): array
    {
        $list = [];

        $set  = auth()->user()->transactions()
            ->whereIn('transactions.account_id', $accounts)
            ->groupBy(['transactions.account_id', 'transaction_journals.user_id'])
            ->get(['transactions.account_id', DB::raw('MAX(transaction_journals.date) AS max_date')]) // @phpstan-ignore-line
        ;

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            $date                          = new Carbon($entry->max_date, config('app.timezone'));
            $date->setTimezone(config('app.timezone'));
            $list[(int)$entry->account_id] = $date;
        }

        return $list;
    }

    /**
     * Get user's locale.
     */
    public function getLocale(): string // get preference
    {
        $locale = app('preferences')->get('locale', config('firefly.default_locale', 'equal'))->data;
        if (is_array($locale)) {
            $locale = 'equal';
        }
        if ('equal' === $locale) {
            $locale = $this->getLanguage();
        }
        $locale = (string)$locale;

        // Check for Windows to replace the locale correctly.
        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            return str_replace('_', '-', $locale);
        }

        return $locale;
    }

    /**
     * Get user's language.
     *
     * @throws FireflyException
     */
    public function getLanguage(): string // get preference
    {
        $preference = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;
        if (!is_string($preference)) {
            throw new FireflyException(sprintf('Preference "language" must be a string, but is unexpectedly a "%s".', gettype($preference)));
        }

        return str_replace('-', '_', $preference);
    }

    public function getLocaleArray(string $locale): array
    {
        return [
            sprintf('%s.utf8', $locale),
            sprintf('%s.UTF-8', $locale),
        ];
    }

    /**
     * Returns the previous URL but refuses to send you to specific URLs.
     *
     * - outside domain
     * - to JS files, API or JSON routes
     *
     * Uses the session's previousUrl() function as inspired by GitHub user @z1r0-
     *
     *  session()->previousUrl() uses getSafeUrl() so we can safely return it:
     */
    public function getSafePreviousUrl(): string
    {
        // Log::debug(sprintf('getSafePreviousUrl: "%s"', session()->previousUrl()));
        return session()->previousUrl() ?? route('index');
    }

    /**
     * Make sure URL is safe.
     */
    public function getSafeUrl(string $unknownUrl, string $safeUrl): string
    {
        // Log::debug(sprintf('getSafeUrl(%s, %s)', $unknownUrl, $safeUrl));
        $returnUrl      = $safeUrl;
        $unknownHost    = parse_url($unknownUrl, PHP_URL_HOST);
        $safeHost       = parse_url($safeUrl, PHP_URL_HOST);

        if (null !== $unknownHost && $unknownHost === $safeHost) {
            $returnUrl = $unknownUrl;
        }

        // URL must not lead to weird pages
        $forbiddenWords = ['jscript', 'json', 'debug', 'serviceworker', 'offline', 'delete', '/login', '/attachments/view'];
        if (Str::contains($returnUrl, $forbiddenWords)) {
            return $safeUrl;
        }

        return $returnUrl;
    }

    public function negative(string $amount): string
    {
        if ('' === $amount) {
            return '0';
        }
        $amount = $this->floatalize($amount);

        if (1 === bccomp($amount, '0')) {
            return bcmul($amount, '-1');
        }

        return $amount;
    }

    /**
     * https://framework.zend.com/downloads/archives
     *
     * Convert a scientific notation to float
     * Additionally fixed a problem with PHP <= 5.2.x with big integers
     */
    public function floatalize(string $value): string
    {
        $value  = strtoupper($value);
        if (!str_contains($value, 'E')) {
            return $value;
        }
        Log::debug(sprintf('Floatalizing %s', $value));

        $number = substr($value, 0, (int)strpos($value, 'E'));
        if (str_contains($number, '.')) {
            $post   = strlen(substr($number, (int)strpos($number, '.') + 1));
            $mantis = substr($value, (int)strpos($value, 'E') + 1);
            if ($mantis < 0) {
                $post += abs((int)$mantis);
            }

            // TODO careless float could break financial math.
            return number_format((float)$value, $post, '.', '');
        }

        // TODO careless float could break financial math.
        return number_format((float)$value, 0, '.', '');
    }

    public function opposite(?string $amount = null): ?string
    {
        if (null === $amount) {
            return null;
        }

        return bcmul($amount, '-1');
    }

    public function phpBytes(string $string): int
    {
        $string = str_replace(['kb', 'mb', 'gb'], ['k', 'm', 'g'], strtolower($string));

        if (false !== stripos($string, 'k')) {
            // has a K in it, remove the K and multiply by 1024.
            $bytes = bcmul(rtrim($string, 'k'), '1024');

            return (int)$bytes;
        }

        if (false !== stripos($string, 'm')) {
            // has a M in it, remove the M and multiply by 1048576.
            $bytes = bcmul(rtrim($string, 'm'), '1048576');

            return (int)$bytes;
        }

        if (false !== stripos($string, 'g')) {
            // has a G in it, remove the G and multiply by (1024)^3.
            $bytes = bcmul(rtrim($string, 'g'), '1073741824');

            return (int)$bytes;
        }

        return (int)$string;
    }

    public function positive(string $amount): string
    {
        if ('' === $amount) {
            return '0';
        }

        try {
            if (-1 === bccomp($amount, '0')) {
                $amount = bcmul($amount, '-1');
            }
        } catch (ValueError $e) {
            Log::error(sprintf('ValueError in Steam::positive("%s"): %s', $amount, $e->getMessage()));
            Log::error($e->getTraceAsString());

            return '0';
        }

        return $amount;
    }

    private function getCurrencies(Collection $accounts): array
    {
        $currencies               = [];
        $accountCurrencies        = [];
        $accountPreferences       = [];
        $primary                  = Amount::getPrimaryCurrency();
        $currencies[$primary->id] = $primary;

        $ids                      = $accounts->pluck('id')->toArray();
        $result                   = AccountMeta::whereIn('account_id', $ids)->where('name', 'currency_id')->get();

        /** @var AccountMeta $item */
        foreach ($result as $item) {
            $integer = (int)$item->data;
            if (0 !== $integer) {
                $accountPreferences[(int)$item->account_id] = $integer;
            }
        }
        // collect those currencies, skip primary because we already have it.
        $set                      = TransactionCurrency::whereIn('id', $accountPreferences)->where('id', '!=', $primary->id)->get();
        foreach ($set as $item) {
            $currencies[$item->id] = $item;
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId       = $account->id;
            $currencyPresent = isset($account->meta) && array_key_exists('currency', $account->meta) && null !== $account->meta['currency'];
            if ($currencyPresent) {
                $currencyId                    = $account->meta['currency']->id;
                $currencies[$currencyId] ??= $account->meta['currency'];
                $accountCurrencies[$accountId] = $account->meta['currency'];
            }
            if (!$currencyPresent && !array_key_exists($accountId, $accountPreferences)) {
                $accountCurrencies[$accountId] = $primary;
            }
            if (!$currencyPresent && array_key_exists($accountId, $accountPreferences)) {
                $accountCurrencies[$accountId] = $currencies[$accountPreferences[$account->id]];
            }
        }

        return $accountCurrencies;
    }
}
