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
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class Steam.
 */
class Steam
{
    public function balanceIgnoreVirtual(Account $account, Carbon $date): string
    {
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $repository->setUser($account->user);

        $currencyId     = (int)$repository->getMetaValue($account, 'currency_id');
        $transactions   = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', $currencyId)
            ->get(['transactions.amount'])->toArray()
        ;
        $nativeBalance  = $this->sumTransactions($transactions, 'amount');

        // get all balances in foreign currency:
        $transactions   = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.foreign_currency_id', $currencyId)
            ->where('transactions.transaction_currency_id', '!=', $currencyId)
            ->get(['transactions.foreign_amount'])->toArray()
        ;

        $foreignBalance = $this->sumTransactions($transactions, 'foreign_amount');

        return bcadd($nativeBalance, $foreignBalance);
    }

    public function sumTransactions(array $transactions, string $key): string
    {
        $sum = '0';

        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $value = (string)($transaction[$key] ?? '0');
            $value = '' === $value ? '0' : $value;
            $sum   = bcadd($sum, $value);
        }

        return $sum;
    }

    /**
     * Gets the balance for the given account during the whole range, using this format:.
     *
     * [yyyy-mm-dd] => 123,2
     *
     * @throws FireflyException
     */
    public function balanceInRange(Account $account, Carbon $start, Carbon $end, ?TransactionCurrency $currency = null): array
    {
        $cache                = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('balance-in-range');
        $cache->addProperty(null !== $currency ? $currency->id : 0);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get();
        }

        $start->subDay();
        $end->addDay();
        $balances             = [];
        $formatted            = $start->format('Y-m-d');
        $startBalance         = $this->balance($account, $start, $currency);

        $balances[$formatted] = $startBalance;
        if (null === $currency) {
            $repository = app(AccountRepositoryInterface::class);
            $repository->setUser($account->user);
            $currency   = $repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        }
        $currencyId           = $currency->id;

        $start->addDay();

        // query!
        $set                  = $account->transactions()
            ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d  23:59:59'))
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
                ]
            )
        ;

        $currentBalance       = $startBalance;

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // normal amount and foreign amount
            $modified        = (string)(null === $entry->modified ? '0' : $entry->modified);
            $foreignModified = (string)(null === $entry->modified_foreign ? '0' : $entry->modified_foreign);
            $amount          = '0';
            if ($currencyId === (int)$entry->transaction_currency_id || 0 === $currencyId) {
                // use normal amount:
                $amount = $modified;
            }
            if ($currencyId === (int)$entry->foreign_currency_id) {
                // use foreign amount:
                $amount = $foreignModified;
            }
            // Log::debug(sprintf('Trying to add %s and %s.', var_export($currentBalance, true), var_export($amount, true)));
            $currentBalance  = bcadd($currentBalance, $amount);
            $carbon          = new Carbon($entry->date, config('app.timezone'));
            $date            = $carbon->format('Y-m-d');
            $balances[$date] = $currentBalance;
        }

        $cache->store($balances);

        return $balances;
    }

    /**
     * Gets balance at the end of current month by default
     *
     * @throws FireflyException
     */
    public function balance(Account $account, Carbon $date, ?TransactionCurrency $currency = null): string
    {
        // abuse chart properties:
        $cache          = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('balance');
        $cache->addProperty($date);
        $cache->addProperty(null !== $currency ? $currency->id : 0);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        if (null === $currency) {
            $currency = $repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        }
        // first part: get all balances in own currency:
        $transactions   = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', $currency->id)
            ->get(['transactions.amount'])->toArray()
        ;
        $nativeBalance  = $this->sumTransactions($transactions, 'amount');
        // get all balances in foreign currency:
        $transactions   = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.foreign_currency_id', $currency->id)
            ->where('transactions.transaction_currency_id', '!=', $currency->id)
            ->get(['transactions.foreign_amount'])->toArray()
        ;
        $foreignBalance = $this->sumTransactions($transactions, 'foreign_amount');
        $balance        = bcadd($nativeBalance, $foreignBalance);
        $virtual        = null === $account->virtual_balance ? '0' : $account->virtual_balance;
        $balance        = bcadd($balance, $virtual);

        $cache->store($balance);

        return $balance;
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function balanceInRangeConverted(Account $account, Carbon $start, Carbon $end, TransactionCurrency $native): array
    {
        $cache                = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('balance-in-range-converted');
        $cache->addProperty($native->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get();
        }
        Log::debug(sprintf('balanceInRangeConverted for account #%d to %s', $account->id, $native->code));
        $start->subDay();
        $end->addDay();
        $balances             = [];
        $formatted            = $start->format('Y-m-d');
        $currencies           = [];
        $startBalance         = $this->balanceConverted($account, $start, $native); // already converted to native amount
        $balances[$formatted] = $startBalance;

        Log::debug(sprintf('Start balance on %s is %s', $formatted, $startBalance));
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $converter            = new ExchangeRateConverter();

        // not sure why this is happening:
        $start->addDay();

        // grab all transactions between start and end:
        $set                  = $account->transactions()
            ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d  23:59:59'))
            ->orderBy('transaction_journals.date', 'ASC')
            ->whereNull('transaction_journals.deleted_at')
            ->get(
                [
                    'transaction_journals.date',
                    'transactions.transaction_currency_id',
                    'transactions.amount',
                    'transactions.foreign_currency_id',
                    'transactions.foreign_amount',
                ]
            )->toArray()
        ;

        // loop the set and convert if necessary:
        $currentBalance       = $startBalance;

        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $day                     = false;

            try {
                $day = Carbon::parse($transaction['date'], config('app.timezone'));
            } catch (InvalidFormatException $e) {
                Log::error(sprintf('Could not parse date "%s" in %s: %s', $transaction['date'], __METHOD__, $e->getMessage()));
            }
            if (false === $day) {
                $day = today(config('app.timezone'));
            }
            $format                  = $day->format('Y-m-d');
            // if the transaction is in the expected currency, change nothing.
            if ((int)$transaction['transaction_currency_id'] === $native->id) {
                // change the current balance, set it to today, continue the loop.
                $currentBalance    = bcadd($currentBalance, $transaction['amount']);
                $balances[$format] = $currentBalance;
                Log::debug(sprintf('%s: transaction in %s, new balance is %s.', $format, $native->code, $currentBalance));

                continue;
            }
            // if foreign currency is in the expected currency, do nothing:
            if ((int)$transaction['foreign_currency_id'] === $native->id) {
                $currentBalance    = bcadd($currentBalance, $transaction['foreign_amount']);
                $balances[$format] = $currentBalance;
                Log::debug(sprintf('%s: transaction in %s (foreign), new balance is %s.', $format, $native->code, $currentBalance));

                continue;
            }
            // otherwise, convert 'amount' to the necessary currency:
            $currencyId              = (int)$transaction['transaction_currency_id'];
            $currency                = $currencies[$currencyId] ?? TransactionCurrency::find($currencyId);
            $currencies[$currencyId] = $currency;

            $rate                    = $converter->getCurrencyRate($currency, $native, $day);
            $convertedAmount         = bcmul($transaction['amount'], $rate);
            $currentBalance          = bcadd($currentBalance, $convertedAmount);
            $balances[$format]       = $currentBalance;

            Log::debug(sprintf(
                '%s: transaction in %s(!). Conversion rate is %s. %s %s = %s %s',
                $format,
                $currency->code,
                $rate,
                $currency->code,
                $transaction['amount'],
                $native->code,
                $convertedAmount
            ));
        }

        $cache->store($balances);
        $converter->summarize();

        return $balances;
    }

    /**
     *  selection of transactions
     *  1: all normal transactions. No foreign currency info. In $currency. Need conversion.
     *  2: all normal transactions. No foreign currency info. In $native. Need NO conversion.
     *  3: all normal transactions. No foreign currency info. In neither currency. Need conversion.
     *  Then, select everything with foreign currency info:
     *  4. All transactions with foreign currency info in $native. Normal currency value is ignored. Do not need
     *  conversion.
     *  5. All transactions with foreign currency info NOT in $native, but currency info in $currency. Need conversion.
     *  6. All transactions with foreign currency info NOT in $native, and currency info NOT in $currency. Need
     *  conversion.
     *
     * Gets balance at the end of current month by default. Returns the balance converted
     * to the indicated currency ($native).
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function balanceConverted(Account $account, Carbon $date, TransactionCurrency $native): string
    {
        Log::debug(sprintf('Now in balanceConverted (%s) for account #%d, converting to %s', $date->format('Y-m-d'), $account->id, $native->code));
        $cache      = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('balance');
        $cache->addProperty($date);
        $cache->addProperty($native->id);
        if ($cache->has()) {
            Log::debug('Cached!');

            // return $cache->get();
        }

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $currency   = $repository->getAccountCurrency($account);
        $currency   = null === $currency ? app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup) : $currency;
        if ($native->id === $currency->id) {
            Log::debug('No conversion necessary!');

            return $this->balance($account, $date);
        }

        $new        = [];
        $existing   = [];
        $new[]      = $account->transactions() // 1
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', $currency->id)
            ->whereNull('transactions.foreign_currency_id')
            ->get(['transaction_journals.date', 'transactions.amount'])->toArray()
        ;
        Log::debug(sprintf('%d transaction(s) in set #1', count($new[0])));
        $existing[] = $account->transactions()         // 2
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', $native->id)
            ->whereNull('transactions.foreign_currency_id')
            ->get(['transactions.amount'])->toArray()
        ;
        Log::debug(sprintf('%d transaction(s) in set #2', count($existing[0])));
        $new[]      = $account->transactions()         // 3
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', '!=', $currency->id)
            ->where('transactions.transaction_currency_id', '!=', $native->id)
            ->whereNull('transactions.foreign_currency_id')
            ->get(['transaction_journals.date', 'transactions.amount'])->toArray()
        ;
        Log::debug(sprintf('%d transactions in set #3', count($new[1])));
        $existing[] = $account->transactions() // 4
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.foreign_currency_id', $native->id)
            ->whereNotNull('transactions.foreign_amount')
            ->get(['transactions.foreign_amount'])->toArray()
        ;
        Log::debug(sprintf('%d transactions in set #4', count($existing[1])));
        $new[]      = $account->transactions()// 5
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', $currency->id)
            ->where('transactions.foreign_currency_id', '!=', $native->id)
            ->whereNotNull('transactions.foreign_amount')
            ->get(['transaction_journals.date', 'transactions.amount'])->toArray()
        ;
        Log::debug(sprintf('%d transactions in set #5', count($new[2])));
        $new[]      = $account->transactions()// 6
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->where('transactions.transaction_currency_id', '!=', $currency->id)
            ->where('transactions.foreign_currency_id', '!=', $native->id)
            ->whereNotNull('transactions.foreign_amount')
            ->get(['transaction_journals.date', 'transactions.amount'])->toArray()
        ;
        Log::debug(sprintf('%d transactions in set #6', count($new[3])));

        // process both sets of transactions. Of course, no need to convert set "existing".
        $balance    = $this->sumTransactions($existing[0], 'amount');
        $balance    = bcadd($balance, $this->sumTransactions($existing[1], 'foreign_amount'));
        Log::debug(sprintf('Balance from set #2 and #4 is %f', $balance));

        // need to convert the others. All sets use the "amount" value as their base (that's easy)
        // but we need to convert each transaction separately because the date difference may
        // incur huge currency changes.
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $start      = clone $date;
        $end        = clone $date;
        $converter  = new ExchangeRateConverter();
        foreach ($new as $set) {
            foreach ($set as $transaction) {
                $currentDate = false;

                try {
                    $currentDate = Carbon::parse($transaction['date'], config('app.timezone'));
                } catch (InvalidFormatException $e) {
                    Log::error(sprintf('Could not parse date "%s" in %s', $transaction['date'], __METHOD__));
                }
                if (false === $currentDate) {
                    $currentDate = today(config('app.timezone'));
                }
                if ($currentDate->lte($start)) {
                    $start = clone $currentDate;
                }
            }
        }
        unset($currentDate);
        $converter->prepare($currency, $native, $start, $end);

        foreach ($new as $set) {
            foreach ($set as $transaction) {
                $currentDate     = false;

                try {
                    $currentDate = Carbon::parse($transaction['date'], config('app.timezone'));
                } catch (InvalidFormatException $e) {
                    Log::error(sprintf('Could not parse date "%s" in %s', $transaction['date'], __METHOD__));
                }
                if (false === $currentDate) {
                    $currentDate = today(config('app.timezone'));
                }
                $rate            = $converter->getCurrencyRate($currency, $native, $currentDate);
                $convertedAmount = bcmul($transaction['amount'], $rate);
                $balance         = bcadd($balance, $convertedAmount);
            }
        }

        // add virtual balance (also needs conversion)
        $virtual    = null === $account->virtual_balance ? '0' : $account->virtual_balance;
        $virtual    = $converter->convert($currency, $native, $account->created_at, $virtual);
        $balance    = bcadd($balance, $virtual);
        $converter->summarize();

        $cache->store($balance);
        $converter->summarize();

        return $balance;
    }

    /**
     * This method always ignores the virtual balance.
     *
     * @throws FireflyException
     */
    public function balancesByAccounts(Collection $accounts, Carbon $date): array
    {
        $ids    = $accounts->pluck('id')->toArray();
        // cache this property.
        $cache  = new CacheProperties();
        $cache->addProperty($ids);
        $cache->addProperty('balances');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }

        // need to do this per account.
        $result = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $result[$account->id] = $this->balance($account, $date);
        }

        $cache->store($result);

        return $result;
    }

    /**
     * This method always ignores the virtual balance.
     *
     * @throws FireflyException
     */
    public function balancesByAccountsConverted(Collection $accounts, Carbon $date): array
    {
        $ids    = $accounts->pluck('id')->toArray();
        // cache this property.
        $cache  = new CacheProperties();
        $cache->addProperty($ids);
        $cache->addProperty('balances-converted');
        $cache->addProperty($date);
        if ($cache->has()) {
            // return $cache->get();
        }

        // need to do this per account.
        $result = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $default = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
            $result[$account->id]
                     = [
                         'balance'        => $this->balance($account, $date),
                         'native_balance' => $this->balanceConverted($account, $date, $default),
                     ];
        }

        $cache->store($result);

        return $result;
    }

    /**
     * Same as above, but also groups per currency.
     */
    public function balancesPerCurrencyByAccounts(Collection $accounts, Carbon $date): array
    {
        $ids    = $accounts->pluck('id')->toArray();
        // cache this property.
        $cache  = new CacheProperties();
        $cache->addProperty($ids);
        $cache->addProperty('balances-per-currency');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }

        // need to do this per account.
        $result = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $result[$account->id] = $this->balancePerCurrency($account, $date);
        }

        $cache->store($result);

        return $result;
    }

    public function balancePerCurrency(Account $account, Carbon $date): array
    {
        // abuse chart properties:
        $cache    = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty('balance-per-currency');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }
        $query    = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
            ->groupBy('transactions.transaction_currency_id')
        ;
        $balances = $query->get(['transactions.transaction_currency_id', \DB::raw('SUM(transactions.amount) as sum_for_currency')]); // @phpstan-ignore-line
        $return   = [];

        /** @var \stdClass $entry */
        foreach ($balances as $entry) {
            $return[(int)$entry->transaction_currency_id] = (string)$entry->sum_for_currency;
        }
        $cache->store($return);

        return $return;
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
     * @throws FireflyException
     */
    public function getHostName(string $ipAddress): string
    {
        try {
            $hostName = gethostbyaddr($ipAddress);
        } catch (\Exception $e) { // intentional generic exception
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return (string)$hostName;
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
            $list[$entry->account_id] = $date;
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
        } catch (\ValueError $e) {
            Log::error(sprintf('ValueError in Steam::positive("%s"): %s', $amount, $e->getMessage()));
            Log::error($e->getTraceAsString());

            return '0';
        }

        return $amount;
    }
}
