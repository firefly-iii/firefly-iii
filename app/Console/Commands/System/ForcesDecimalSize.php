<?php

/*
 * ForceDecimalSize.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * This command was inspired by https://github.com/elliot-gh. It will check all amount fields
 * and their values and correct them to the correct number of decimal places. This fixes issues where
 * Firefly III would store 0.01 as 0.01000000000000000020816681711721685132943093776702880859375.
 */
class ForcesDecimalSize extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'This command resizes DECIMAL columns in MySQL or PostgreSQL and correct amounts (only MySQL).';
    protected $signature   = 'firefly-iii:force-decimal-size';
    private string $cast;
    private array  $classes
                           = [
            'accounts'                 => Account::class,
            'auto_budgets'             => AutoBudget::class,
            'available_budgets'        => AvailableBudget::class,
            'bills'                    => Bill::class,
            'budget_limits'            => BudgetLimit::class,
            'piggy_bank_events'        => PiggyBankEvent::class,
            'piggy_bank_repetitions'   => PiggyBankRepetition::class,
            'piggy_banks'              => PiggyBank::class,
            'recurrences_transactions' => RecurrenceTransaction::class,
            'transactions'             => Transaction::class,
        ];

    private string $operator;
    private string $regularExpression;
    private array  $tables
                           = [
            'accounts'                 => ['virtual_balance'],
            'auto_budgets'             => ['amount'],
            'available_budgets'        => ['amount'],
            'bills'                    => ['amount_min', 'amount_max'],
            'budget_limits'            => ['amount'],
            'currency_exchange_rates'  => ['rate', 'user_rate'],
            'limit_repetitions'        => ['amount'],
            'piggy_bank_events'        => ['amount'],
            'piggy_bank_repetitions'   => ['current_amount'],
            'piggy_banks'              => ['target_amount'],
            'recurrences_transactions' => ['amount', 'foreign_amount'],
            'transactions'             => ['amount', 'foreign_amount'],
        ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app('log')->debug('Now in ForceDecimalSize::handle()');
        $this->determineDatabaseType();

        $this->friendlyError('Running this command is dangerous and can cause data loss.');
        $this->friendlyError('Please do not continue.');
        $question = $this->confirm('Do you want to continue?');
        if (true === $question) {
            $this->correctAmounts();
            $this->updateDecimals();
        }

        return 0;
    }

    private function determineDatabaseType(): void
    {
        // switch stuff based on database connection:
        $this->operator          = 'REGEXP';
        $this->regularExpression = '\'\\\.[\\\d]{%d}[1-9]+\'';
        $this->cast              = 'CHAR';
        if ('pgsql' === config('database.default')) {
            $this->operator          = 'SIMILAR TO';
            $this->regularExpression = '\'%%\.[\d]{%d}[1-9]+%%\'';
            $this->cast              = 'TEXT';
        }
        if ('sqlite' === config('database.default')) {
            $this->regularExpression = '"\.[\d]{%d}[1-9]+"';
        }
    }

    /**
     * This method checks if a basic check can be done or if it needs to be complicated.
     */
    private function correctAmounts(): void
    {
        // if sqlite, add function?
        if ('sqlite' === (string) config('database.default')) {
            DB::connection()->getPdo()->sqliteCreateFunction('REGEXP', static function ($pattern, $value) {
                mb_regex_encoding('UTF-8');
                $pattern = trim($pattern, '"');

                return (false !== mb_ereg($pattern, (string) $value)) ? 1 : 0;
            });
        }

        if (!in_array((string) config('database.default'), ['mysql', 'pgsql', 'sqlite'], true)) {
            $this->friendlyWarning(sprintf('Skip correcting amounts, does not support "%s"...', (string) config('database.default')));

            return;
        }
        $this->correctAmountsByCurrency();
    }

    /**
     * This method loops all enabled currencies and then calls the method that will fix all objects in this currency.
     *
     * @throws FireflyException
     */
    private function correctAmountsByCurrency(): void
    {
        /** @var Collection $enabled */
        $enabled = TransactionCurrency::whereEnabled(1)->get();

        /** @var TransactionCurrency $currency */
        foreach ($enabled as $currency) {
            $this->correctByCurrency($currency);
        }
    }

    /**
     * This method loops the available tables that may need fixing, and calls for the right method that can fix them.
     *
     * @throws FireflyException
     */
    private function correctByCurrency(TransactionCurrency $currency): void
    {
        /**
         * @var string $name
         * @var array  $fields
         */
        foreach ($this->tables as $name => $fields) {
            switch ($name) { // @phpstan-ignore-line
                default:
                    $message = sprintf('Cannot handle table "%s"', $name);
                    $this->friendlyError($message);

                    throw new FireflyException($message);

                case 'accounts':
                    $this->correctAccountAmounts($currency, $fields);

                    break;

                case 'auto_budgets':
                case 'available_budgets':
                case 'bills':
                case 'budget_limits':
                case 'recurrences_transactions':
                    $this->correctGeneric($currency, $name);

                    break;

                case 'currency_exchange_rates':
                case 'limit_repetitions':
                    // do nothing
                    break;

                case 'piggy_bank_events':
                    $this->correctPiggyEventAmounts($currency, $fields);

                    break;

                case 'piggy_bank_repetitions':
                    $this->correctPiggyRepetitionAmounts($currency, $fields);

                    break;

                case 'piggy_banks':
                    $this->correctPiggyAmounts($currency, $fields);

                    break;

                case 'transactions':
                    $this->correctTransactionAmounts($currency);

                    break;
            }
        }
    }

    /**
     * This method loops over all accounts and validates the amounts.
     */
    private function correctAccountAmounts(TransactionCurrency $currency, array $fields): void
    {
        $operator          = $this->operator;
        $cast              = $this->cast;
        $regularExpression = $this->regularExpression;

        /** @var Builder $query */
        $query             = Account::leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('account_meta.name', 'currency_id')
            ->where('account_meta.data', json_encode((string) $currency->id))
        ;
        $query->where(static function (Builder $q) use ($fields, $currency, $operator, $cast, $regularExpression): void {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(accounts.%s AS %s)', $field, $cast)), // @phpstan-ignore-line
                    $operator,
                    DB::raw(sprintf($regularExpression, $currency->decimal_places))
                );
            }
        });
        $result            = $query->get(['accounts.*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All accounts in %s are OK', $currency->code));

            return;
        }

        /** @var Account $account */
        foreach ($result as $account) {
            /** @var string $field */
            foreach ($fields as $field) {
                $value   = $account->{$field};
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = 10 ** $currency->decimal_places;
                $correct = bcdiv((string) round($value * $pow), (string) $pow, 12);
                $this->friendlyInfo(sprintf('Account #%d has %s with value "%s", this has been corrected to "%s".', $account->id, $field, $value, $correct));
                Account::find($account->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all auto budgets in currency $currency.
     */
    private function correctGeneric(TransactionCurrency $currency, string $table): void
    {
        $class             = $this->classes[$table];
        $fields            = $this->tables[$table];
        $operator          = $this->operator;
        $cast              = $this->cast;
        $regularExpression = $this->regularExpression;

        /** @var Builder $query */
        $query             = $class::where('transaction_currency_id', $currency->id)->where(
            static function (Builder $q) use ($fields, $currency, $operator, $cast, $regularExpression): void {
                /** @var string $field */
                foreach ($fields as $field) {
                    $q->orWhere(
                        DB::raw(sprintf('CAST(%s AS %s)', $field, $cast)), // @phpstan-ignore-line
                        $operator,
                        DB::raw(sprintf($regularExpression, $currency->decimal_places))
                    );
                }
            }
        );

        $result            = $query->get(['*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All %s in %s are OK', $table, $currency->code));

            return;
        }

        /** @var Model $item */
        foreach ($result as $item) {
            /** @var string $field */
            foreach ($fields as $field) {
                $value   = $item->{$field};
                if (null === $value || '' === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = 10 ** $currency->decimal_places;
                $correct = bcdiv((string) round($value * $pow), (string) $pow, 12);
                $this->friendlyWarning(sprintf('%s #%d has %s with value "%s", this has been corrected to "%s".', $table, $item->id, $field, $value, $correct));
                $class::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all piggy bank events in currency $currency.
     */
    private function correctPiggyEventAmounts(TransactionCurrency $currency, array $fields): void
    {
        $operator          = $this->operator;
        $cast              = $this->cast;
        $regularExpression = $this->regularExpression;

        /** @var Builder $query */
        $query             = PiggyBankEvent::leftJoin('piggy_banks', 'piggy_bank_events.piggy_bank_id', '=', 'piggy_banks.id')
            ->leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('account_meta.name', 'currency_id')
            ->where('account_meta.data', json_encode((string) $currency->id))
            ->where(static function (Builder $q) use ($fields, $currency, $cast, $operator, $regularExpression): void {
                foreach ($fields as $field) {
                    $q->orWhere(
                        DB::raw(sprintf('CAST(piggy_bank_events.%s AS %s)', $field, $cast)), // @phpstan-ignore-line
                        $operator,
                        DB::raw(sprintf($regularExpression, $currency->decimal_places))
                    );
                }
            })
        ;

        $result            = $query->get(['piggy_bank_events.*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All piggy bank events in %s are OK', $currency->code));

            return;
        }

        /** @var PiggyBankEvent $item */
        foreach ($result as $item) {
            /** @var string $field */
            foreach ($fields as $field) {
                $value   = $item->{$field};
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = 10 ** $currency->decimal_places;
                $correct = bcdiv((string) round($value * $pow), (string) $pow, 12);
                $this->friendlyWarning(
                    sprintf('Piggy bank event #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct)
                );
                PiggyBankEvent::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all piggy bank repetitions in currency $currency.
     */
    private function correctPiggyRepetitionAmounts(TransactionCurrency $currency, array $fields): void
    {
        $operator          = $this->operator;
        $cast              = $this->cast;
        $regularExpression = $this->regularExpression;

        // select all piggy bank repetitions with this currency and issue.
        /** @var Builder $query */
        $query             = PiggyBankRepetition::leftJoin('piggy_banks', 'piggy_bank_repetitions.piggy_bank_id', '=', 'piggy_banks.id')
            ->leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('account_meta.name', 'currency_id')
            ->where('account_meta.data', json_encode((string) $currency->id))
            ->where(static function (Builder $q) use ($fields, $currency, $operator, $cast, $regularExpression): void {
                foreach ($fields as $field) {
                    $q->orWhere(
                        DB::raw(sprintf('CAST(piggy_bank_repetitions.%s AS %s)', $field, $cast)), // @phpstan-ignore-line
                        $operator,
                        DB::raw(sprintf($regularExpression, $currency->decimal_places))
                    );
                }
            })
        ;

        $result            = $query->get(['piggy_bank_repetitions.*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All piggy bank repetitions in %s', $currency->code));

            return;
        }

        /** @var PiggyBankRepetition $item */
        foreach ($result as $item) {
            /** @var string $field */
            foreach ($fields as $field) {
                $value   = $item->{$field};
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = 10 ** $currency->decimal_places;
                $correct = bcdiv((string) round($value * $pow), (string) $pow, 12);
                $this->friendlyWarning(
                    sprintf('Piggy bank repetition #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct)
                );
                PiggyBankRepetition::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all piggy banks in currency $currency.
     */
    private function correctPiggyAmounts(TransactionCurrency $currency, array $fields): void
    {
        $operator          = $this->operator;
        $cast              = $this->cast;
        $regularExpression = $this->regularExpression;

        /** @var Builder $query */
        $query             = PiggyBank::leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('account_meta.name', 'currency_id')
            ->where('account_meta.data', json_encode((string) $currency->id))
            ->where(static function (Builder $q) use ($fields, $currency, $operator, $cast, $regularExpression): void {
                foreach ($fields as $field) {
                    $q->orWhere(
                        DB::raw(sprintf('CAST(piggy_banks.%s AS %s)', $field, $cast)), // @phpstan-ignore-line
                        $operator,
                        DB::raw(sprintf($regularExpression, $currency->decimal_places))
                    );
                }
            })
        ;

        $result            = $query->get(['piggy_banks.*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All piggy banks in %s are OK', $currency->code));

            return;
        }

        /** @var PiggyBank $item */
        foreach ($result as $item) {
            /** @var string $field */
            foreach ($fields as $field) {
                $value   = $item->{$field};
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = 10 ** $currency->decimal_places;
                $correct = bcdiv((string) round($value * $pow), (string) $pow, 12);
                $this->friendlyWarning(sprintf('Piggy bank #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                PiggyBank::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all transactions in currency $currency.
     */
    private function correctTransactionAmounts(TransactionCurrency $currency): void
    {
        // select all transactions with this currency and issue.
        /** @var Builder $query */
        $query  = Transaction::where('transaction_currency_id', $currency->id)->where(
            DB::raw(sprintf('CAST(amount as %s)', $this->cast)), // @phpstan-ignore-line
            $this->operator,
            DB::raw(sprintf($this->regularExpression, $currency->decimal_places))
        );

        $result = $query->get(['transactions.*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All transactions in %s are OK', $currency->code));
        }

        /** @var Transaction $item */
        foreach ($result as $item) {
            $value   = $item->amount;
            if ('' === $value) {
                continue;
            }
            // fix $field by rounding it down correctly.
            $pow     = (float) 10 ** $currency->decimal_places;
            $correct = bcdiv((string) round((float) $value * $pow), (string) $pow, 12);
            $this->friendlyWarning(sprintf('Transaction #%d has amount with value "%s", this has been corrected to "%s".', $item->id, $value, $correct));
            Transaction::find($item->id)->update(['amount' => $correct]);
        }

        // select all transactions with this FOREIGN currency and issue.
        /** @var Builder $query */
        $query  = Transaction::where('foreign_currency_id', $currency->id)->where(
            DB::raw(sprintf('CAST(foreign_amount as %s)', $this->cast)), // @phpstan-ignore-line
            $this->operator,
            DB::raw(sprintf($this->regularExpression, $currency->decimal_places))
        );

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->friendlyPositive(sprintf('All transactions in foreign currency %s are OK', $currency->code));

            return;
        }

        /** @var Transaction $item */
        foreach ($result as $item) {
            $value   = $item->foreign_amount;
            if (null === $value) {
                continue;
            }
            // fix $field by rounding it down correctly.
            $pow     = (float) 10 ** $currency->decimal_places;
            $correct = bcdiv((string) round((float) $value * $pow), (string) $pow, 12);
            $this->friendlyWarning(
                sprintf('Transaction #%d has foreign amount with value "%s", this has been corrected to "%s".', $item->id, $value, $correct)
            );
            Transaction::find($item->id)->update(['foreign_amount' => $correct]);
        }
    }

    private function updateDecimals(): void
    {
        $this->friendlyInfo('Going to force the size of DECIMAL columns. Please hold.');
        $type = (string) config('database.default');

        /**
         * @var string $name
         * @var array  $fields
         */
        foreach ($this->tables as $name => $fields) {
            /** @var string $field */
            foreach ($fields as $field) {
                $this->friendlyLine(sprintf('Updating table "%s", field "%s"...', $name, $field));
                if ('pgsql' === $type) {
                    DB::select(sprintf('ALTER TABLE %s ALTER COLUMN %s TYPE DECIMAL(32,12);', $name, $field));
                    sleep(1);

                    return;
                }
                if ('mysql' === $type) {
                    DB::select(sprintf('ALTER TABLE %s CHANGE COLUMN %s %s DECIMAL(32, 12);', $name, $field, $field));
                    sleep(1);

                    return;
                }
                $this->friendlyError(sprintf('Cannot handle database type "%s".', $type));
            }
        }
    }
}
