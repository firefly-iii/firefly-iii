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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class Steam.
 */
class Steam
{
    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $type   = $account->accountType->type;
        $list   = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $result = $account->accountMeta->where('name', 'currency_id')->first();
        if (null === $result) {
            return null;
        }

        return TransactionCurrency::find((int) $result->data);
    }

    private function sumTransactions(array $transactions, string $key): string
    {
        $sum = '0';

        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $value = (string) ($transaction[$key] ?? '0');
            $value = '' === $value ? '0' : $value;
            $sum   = bcadd($sum, $value);
            // Log::debug(sprintf('Add value from "%s": %s', $key, $value));
        }
        Log::debug(sprintf('Sum of "%s"-fields is %s', $key, $sum));

        return $sum;
    }

    public function finalAccountBalanceInRange(Account $account, Carbon $start, Carbon $end, bool $convertToNative): array
    {
        // expand period.
        $start->subDay()->startOfDay();
        $end->addDay()->endOfDay();
        Log::debug(sprintf('finalAccountBalanceInRange(#%d, %s, %s)', $account->id, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        // set up cache
        $cache                = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('final-balance-in-range');
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get();
        }

        $balances             = [];
        $formatted            = $start->format('Y-m-d');
        $startBalance         = $this->finalAccountBalance($account, $start);
        $defaultCurrency      = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        $accountCurrency      = $this->getAccountCurrency($account);
        $hasCurrency          = null !== $accountCurrency;
        $currency             = $accountCurrency ?? $defaultCurrency;
        Log::debug(sprintf('Currency is %s', $currency->code));
        if (!$hasCurrency) {
            Log::debug(sprintf('Also set start balance in %s', $defaultCurrency->code));
            $startBalance[$defaultCurrency->code] ??= '0';
        }
        $currencies           = [
            $currency->id        => $currency,
            $defaultCurrency->id => $defaultCurrency,
        ];


        $startBalance[$currency->code] ??= '0';
        $balances[$formatted] = $startBalance;
        Log::debug('Final start balance: ', $startBalance);


        // sums up the balance changes per day, for foreign, native and normal amounts.
        $set                  = $account->transactions()
            ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d H:i:s'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d  H:i:s'))
            ->groupBy('transaction_journals.date')
            ->groupBy('transactions.transaction_currency_id')
            ->groupBy('transactions.foreign_currency_id')
            ->orderBy('transaction_journals.date', 'ASC')
            ->whereNull('transaction_journals.deleted_at')
            ->get(
                [ // @phpstan-ignore-line
                    'transaction_journals.date',
                    'transactions.transaction_currency_id',
                    DB::raw('SUM(transactions.amount) AS modified'),
                    'transactions.foreign_currency_id',
                    DB::raw('SUM(transactions.foreign_amount) AS modified_foreign'),
                    DB::raw('SUM(transactions.native_amount) AS modified_native'),
                ]
            )
        ;

        $currentBalance       = $startBalance;

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // normal, native and foreign amount
            $carbon                             = new Carbon($entry->date, $entry->date_tz);
            $modified                           = (string) (null === $entry->modified ? '0' : $entry->modified);
            $foreignModified                    = (string) (null === $entry->modified_foreign ? '0' : $entry->modified_foreign);
            $nativeModified                     = (string) (null === $entry->modified_native ? '0' : $entry->modified_native);

            // find currency of this entry.
            $currencies[$entry->transaction_currency_id] ??= TransactionCurrency::find($entry->transaction_currency_id);
            $entryCurrency                      = $currencies[$entry->transaction_currency_id];

            Log::debug(sprintf('Processing transaction(s) on date %s', $carbon->format('Y-m-d H:i:s')));

            // if convert to native, if NOT convert to native.
            if ($convertToNative) {
                Log::debug(sprintf('Amount is %s %s, foreign amount is %s, native amount is %s', $entryCurrency->code, $this->bcround($modified, 2), $this->bcround($foreignModified, 2), $this->bcround($nativeModified, 2)));
                // if the currency is the default currency add to native balance + currency balance
                if ($entry->transaction_currency_id === $defaultCurrency->id) {
                    Log::debug('Add amount to native.');
                    $currentBalance['native_balance'] = bcadd($currentBalance['native_balance'], $modified);
                }

                // add to native balance.
                if ($entry->foreign_currency_id !== $defaultCurrency->id) {
                    // this check is not necessary, because if the foreign currency is the same as the default currency, the native amount is zero.
                    // so adding this would mean nothing.
                    $currentBalance['native_balance'] = bcadd($currentBalance['native_balance'], $nativeModified);
                }
                if ($entry->foreign_currency_id === $defaultCurrency->id) {
                    $currentBalance['native_balance'] = bcadd($currentBalance['native_balance'], $foreignModified);
                }
                // add to balance if is the same.
                if ($entry->transaction_currency_id === $accountCurrency?->id) {
                    $currentBalance['balance'] = bcadd($currentBalance['balance'], $modified);
                }
                // add currency balance
                $currentBalance[$entryCurrency->code] = bcadd($currentBalance[$entryCurrency->code] ?? '0', $modified);
            }
            if (!$convertToNative) {
                Log::debug(sprintf('Amount is %s %s, foreign amount is %s, native amount is %s', $entryCurrency->code, $modified, $foreignModified, $nativeModified));
                // add to balance, as expected.
                $currentBalance['balance']            = bcadd($currentBalance['balance'] ?? '0', $modified);
                // add to GBP, as expected.
                $currentBalance[$entryCurrency->code] = bcadd($currentBalance[$entryCurrency->code] ?? '0', $modified);
            }
            $balances[$carbon->format('Y-m-d')] = $currentBalance;
            Log::debug('Updated entry', $currentBalance);
        }
        $cache->store($balances);
        Log::debug('End of method');

        return $balances;
    }

    public function finalAccountsBalance(Collection $accounts, Carbon $date): array
    {
        $balances = [];
        foreach ($accounts as $account) {
            $balances[$account->id] = $this->finalAccountBalance($account, $date);
        }

        return $balances;
    }

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

    /**
     * Returns the balance of an account at exact moment given. Array with at least one value.
     *
     * "balance" the balance in whatever currency the account has, so the sum of all transaction that happen to have
     * THAT currency.
     * "native_balance" the balance according to the "native_amount" + "native_foreign_amount" fields.
     * "ABC" the balance in this particular currency code (may repeat for each found currency).
     *
     * Het maakt niet uit of de native currency wel of niet gelijk is aan de account currency.
     * Optelsom zou hetzelfde moeten zijn. Als het EUR is en de rekening ook is native_amount 0.
     * Zo niet is amount 0 en native_amount het bedrag.
     *
     * Eerst een som van alle transacties in de native currency. Alle EUR bij elkaar opgeteld.
     * Om te weten wat er nog meer op de rekening gebeurt, pak alles waar currency niet EUR is, en de foreign ook niet,
     * en tel native_amount erbij op.
     * Daarna pak je alle transacties waar currency niet EUR is, en de foreign wel, en tel foreign_amount erbij op.
     *
     * Wil je niks weten van native currencies, pak je:
     *
     * Eerst een som van alle transacties gegroepeerd op currency. Einde.
     */
    public function finalAccountBalance(Account $account, Carbon $date): array
    {
        Log::debug(sprintf('Now in finalAccountBalance(#%d, "%s", "%s")', $account->id, $account->name, $date->format('Y-m-d H:i:s')));
        $native          = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        $convertToNative = app('preferences')->getForUser($account->user, 'convert_to_native', false)->data;
        $accountCurrency = $this->getAccountCurrency($account);
        $hasCurrency     = null !== $accountCurrency;
        $currency        = $hasCurrency ? $accountCurrency : $native;
        $return          = [];

        // first, the "balance", as described earlier.
        if ($convertToNative) {
            // normal balance
            $return['balance']        = (string) $account->transactions()
                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
                ->where('transactions.transaction_currency_id', $native->id)
                ->sum('transactions.amount')
            ;
            // plus virtual balance, if the account has a virtual_balance in the native currency
            if ($native->id === $accountCurrency?->id) {
                $return['balance'] = bcadd('' === (string) $account->virtual_balance ? '0' : $account->virtual_balance, $return['balance']);
            }
            Log::debug(sprintf('balance is (%s only) %s (with virtual balance)', $native->code, $this->bcround($return['balance'], 2)));

            // native balance
            $return['native_balance'] = (string) $account->transactions()
                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
                ->whereNot('transactions.transaction_currency_id', $native->id)
                ->sum('transactions.native_amount')
            ;
            // plus native virtual balance.
            $return['native_balance'] = bcadd('' === (string) $account->native_virtual_balance ? '0' : $account->native_virtual_balance, $return['native_balance']);
            Log::debug(sprintf('native_balance is (all transactions to %s) %s (with virtual balance)', $native->code, $this->bcround($return['native_balance'])));

            // plus foreign transactions in THIS currency.
            $sum                      = (string) $account->transactions()
                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
                ->whereNot('transactions.transaction_currency_id', $native->id)
                ->where('transactions.foreign_currency_id', $native->id)
                ->sum('transactions.foreign_amount')
            ;
            $return['native_balance'] = bcadd($return['native_balance'], $sum);

            Log::debug(sprintf('Foreign amount transactions add (%s only) %s, total native_balance is now %s', $native->code, $this->bcround($sum), $this->bcround($return['native_balance'])));
        }

        // balance(s) in other (all) currencies.
        $array           = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transactions.transaction_currency_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
            ->get(['transaction_currencies.code', 'transactions.amount'])->toArray()
        ;
        $others          = $this->groupAndSumTransactions($array, 'code', 'amount');
        Log::debug('All balances are (joined)', $others);
        // if the account has no own currency preference, drop balance in favor of native balance
        if ($hasCurrency && !$convertToNative) {
            $return['balance']        = $others[$currency->code] ?? '0';
            $return['native_balance'] = $others[$currency->code] ?? '0';
            Log::debug(sprintf('Set balance + native_balance to %s', $return['balance']));
        }

        // if the currency is the same as the native currency, set the native_balance to the balance for consistency.
        //        if($currency->id === $native->id) {
        //            $return['native_balance'] = $return['balance'];
        //        }

        if (!$hasCurrency && array_key_exists('balance', $return) && array_key_exists('native_balance', $return)) {
            Log::debug('Account has no currency preference, dropping balance in favor of native balance.');
            $sum                      = bcadd($return['balance'], $return['native_balance']);
            Log::debug(sprintf('%s + %s = %s', $return['balance'], $return['native_balance'], $sum));
            $return['native_balance'] = $sum;
            unset($return['balance']);
        }
        $final           = array_merge($return, $others);
        Log::debug('Return is', $final);

        return $final;
    }

    public function filterAccountBalances(array $total, Account $account, bool $convertToNative, ?TransactionCurrency $currency = null): array
    {
        Log::debug(sprintf('filterAccountBalances(#%d)', $account->id));
        $return = [];
        foreach ($total as $key => $value) {
            $return[$key] = $this->filterAccountBalance($value, $account, $convertToNative, $currency);
        }
        Log::debug(sprintf('end of filterAccountBalances(#%d)', $account->id));

        return $return;
    }

    public function filterAccountBalance(array $set, Account $account, bool $convertToNative, ?TransactionCurrency $currency = null): array
    {
        Log::debug(sprintf('filterAccountBalance(#%d)', $account->id), $set);
        if (0 === count($set)) {
            Log::debug(sprintf('Return empty array for account #%d', $account->id));

            return [];
        }
        $defaultCurrency = app('amount')->getDefaultCurrency();
        if ($convertToNative) {
            if ($defaultCurrency->id === $currency?->id) {
                Log::debug(sprintf('Unset "native_balance" and "%s" for account #%d', $defaultCurrency->code, $account->id));
                unset($set['native_balance'], $set[$defaultCurrency->code]);
            }
            if (null !== $currency && $defaultCurrency->id !== $currency->id) {
                Log::debug(sprintf('Unset balance for account #%d', $account->id));
                unset($set['balance']);
            }

            if (null === $currency) {
                Log::debug(sprintf('TEMP DO NOT Drop defaultCurrency balance for account #%d', $account->id));
                // unset($set[$this->defaultCurrency->code]);
            }
        }

        if (!$convertToNative) {
            if (null === $currency) {
                Log::debug(sprintf('Unset native_balance and make defaultCurrency balance the balance for account #%d', $account->id));
                $set['balance'] = $set[$defaultCurrency->code] ?? '0';
                unset($set['native_balance'], $set[$defaultCurrency->code]);
            }

            if (null !== $currency) {
                Log::debug(sprintf('Unset native_balance + defaultCurrency + currencyCode balance for account #%d', $account->id));
                unset($set['native_balance'], $set[$defaultCurrency->code], $set[$currency->code]);
            }
        }


        // put specific value first in array.
        if (array_key_exists('native_balance', $set)) {
            $set = ['native_balance' => $set['native_balance']] + $set;
        }
        if (array_key_exists('balance', $set)) {
            $set = ['balance' => $set['balance']] + $set;
        }
        Log::debug(sprintf('Return #%d', $account->id), $set);

        return $set;
    }

    private function groupAndSumTransactions(array $array, string $group, string $field): array
    {
        $return = [];

        foreach ($array as $item) {
            $groupKey          = $item[$group] ?? 'unknown';
            $return[$groupKey] = bcadd($return[$groupKey] ?? '0', $item[$field]);
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    public function getHostName(string $ipAddress): string
    {
        $host = '';

        try {
            $hostName = gethostbyaddr($ipAddress);
        } catch (\Exception $e) {
            app('log')->error($e->getMessage());
            $hostName = $ipAddress;
        }

        if ('' !== (string) $hostName && $hostName !== $ipAddress) {
            $host = $hostName;
        }

        return (string) $host;
    }

    public function getLastActivities(array $accounts): array
    {
        $list = [];

        $set  = auth()->user()->transactions()
            ->whereIn('transactions.account_id', $accounts)
            ->groupBy(['transactions.account_id', 'transaction_journals.user_id'])
            ->get(['transactions.account_id', \DB::raw('MAX(transaction_journals.date) AS max_date')]) // @phpstan-ignore-line
        ;

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            $date                           = new Carbon($entry->max_date, config('app.timezone'));
            $date->setTimezone(config('app.timezone'));
            $list[(int) $entry->account_id] = $date;
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
        $locale = (string) $locale;

        // Check for Windows to replace the locale correctly.
        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            $locale = str_replace('_', '-', $locale);
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
        if (\Str::contains($returnUrl, $forbiddenWords)) {
            $returnUrl = $safeUrl;
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
            $amount = bcmul($amount, '-1');
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

        $number = substr($value, 0, (int) strpos($value, 'E'));
        if (str_contains($number, '.')) {
            $post   = strlen(substr($number, (int) strpos($number, '.') + 1));
            $mantis = substr($value, (int) strpos($value, 'E') + 1);
            if ($mantis < 0) {
                $post += abs((int) $mantis);
            }

            // TODO careless float could break financial math.
            return number_format((float) $value, $post, '.', '');
        }

        // TODO careless float could break financial math.
        return number_format((float) $value, 0, '.', '');
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

            return (int) $bytes;
        }

        if (false !== stripos($string, 'm')) {
            // has a M in it, remove the M and multiply by 1048576.
            $bytes = bcmul(rtrim($string, 'm'), '1048576');

            return (int) $bytes;
        }

        if (false !== stripos($string, 'g')) {
            // has a G in it, remove the G and multiply by (1024)^3.
            $bytes = bcmul(rtrim($string, 'g'), '1073741824');

            return (int) $bytes;
        }

        return (int) $string;
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
        } catch (\ValueError $e) {
            Log::error(sprintf('ValueError in Steam::positive("%s"): %s', $amount, $e->getMessage()));
            Log::error($e->getTraceAsString());

            return '0';
        }

        return $amount;
    }
}
