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
        }

        return $sum;
    }

    public function finalAccountBalanceInRange(Account $account, Carbon $start, Carbon $end): array
    {
        // expand period.
        $start->subDay()->startOfDay();
        $end->addDay()->endOfDay();

        // set up cache
        $cache                = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('final-balance-in-range');
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            // return $cache->get();
        }

        $balances             = [];
        $formatted            = $start->format('Y-m-d');
        $startBalance         = $this->finalAccountBalance($account, $start);
        $defaultCurrency      = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        $currency             = $this->getAccountCurrency($account) ?? $defaultCurrency;
        $currencies           = [
            $currency->id        => $currency,
            $defaultCurrency->id => $defaultCurrency,
        ];
        $startBalance[$defaultCurrency->code] ??= '0';
        $startBalance[$currency->code]        ??= '0';
        $balances[$formatted] = $startBalance;


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
                    \DB::raw('SUM(transactions.amount) AS modified'),
                    'transactions.foreign_currency_id',
                    \DB::raw('SUM(transactions.foreign_amount) AS modified_foreign'),
                    \DB::raw('SUM(transactions.native_amount) AS modified_native'),
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

            // add "modified" to amount if the currency id matches the account currency id.
            if ($entry->transaction_currency_id === $currency->id) {
                $currentBalance['balance']       = bcadd($currentBalance['balance'], $modified);
                $currentBalance[$currency->code] = bcadd($currentBalance[$currency->code], $modified);
            }

            // always add the native balance, even if it ends up at zero.
            $currentBalance['native_balance']   = bcadd($currentBalance['native_balance'], $nativeModified);

            // add modified foreign to the array
            if (null !== $entry->foreign_currency_id) {
                $foreignId                              = $entry->foreign_currency_id;
                $currencies[$foreignId]                 ??= TransactionCurrency::find($foreignId);
                $foreignCurrency                        = $currencies[$foreignId];
                $currentBalance[$foreignCurrency->code] ??= '0';
                $currentBalance[$foreignCurrency->code] = bcadd($currentBalance[$foreignCurrency->code], $foreignModified);
            }
            $balances[$carbon->format('Y-m-d')] = $currentBalance;
        }
        $cache->store($balances);

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
     */
    public function finalAccountBalance(Account $account, Carbon $date): array
    {
        $native            = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        $currency          = $this->getAccountCurrency($account) ?? $native;
        $return            = [
            'native_balance' => '0',
        ];
        Log::debug(sprintf('Now in finalAccountBalance("%s", "%s")', $account->name, $date->format('Y-m-d H:i:s')));
        // first, the "balance", as described earlier.
        $array             = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
            ->where('transactions.transaction_currency_id', $currency->id)
            ->get(['transactions.amount'])->toArray()
        ;
        $return['balance'] = $this->sumTransactions($array, 'amount');
        //Log::debug(sprintf('balance is %s', $return['balance']));
        // add virtual balance:
        $return['balance'] = bcadd('' === (string) $account->virtual_balance ? '0' : $account->virtual_balance, $return['balance']);
        //Log::debug(sprintf('balance is %s (with virtual balance)', $return['balance']));

        // then, native balance (if necessary(
        if ($native->id !== $currency->id) {
            $array                    = $account->transactions()
                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
                ->get(['transactions.native_amount'])->toArray()
            ;
            $return['native_balance'] = $this->sumTransactions($array, 'native_amount');
//            Log::debug(sprintf('native_balance is %s', $return['native_balance']));
            $return['native_balance'] = bcadd('' === (string) $account->native_virtual_balance ? '0' : $account->native_virtual_balance, $return['balance']);
//            Log::debug(sprintf('native_balance is %s (with virtual balance)', $return['native_balance']));
        }

        // balance(s) in other currencies.
        $array  = $account->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transactions.transaction_currency_id')
                          ->where('transaction_journals.date', '<=', $date->format('Y-m-d H:i:s'))
                          ->get(['transaction_currencies.code', 'transactions.amount'])->toArray();
        $others = $this->groupAndSumTransactions($array, 'code', 'amount');
//        Log::debug('All others are (joined)', $others);

        return array_merge($return, $others);
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
            $date                     = new Carbon($entry->max_date, config('app.timezone'));
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
