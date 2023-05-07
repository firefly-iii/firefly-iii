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

use FireflyIII\Console\Commands\VerifiesAccessToken;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class ForceDecimalSize
 *
 * This command was inspired by https://github.com/elliot-gh. It will check all amount fields
 * and their values and correct them to the correct number of decimal places. This fixes issues where
 * Firefly III would store 0.01 as 0.01000000000000000020816681711721685132943093776702880859375.
 */
class ForceDecimalSize extends Command
{
    use VerifiesAccessToken;

    protected $description = 'This command resizes DECIMAL columns in MySQL or PostgreSQL and correct amounts (only MySQL).';
    protected $signature   = 'firefly-iii:force-decimal-size
                                {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}';
    private array $decimals    = [];
    private array $tables      = [
        'accounts'                 => ['virtual_balance'],
        'auto_budgets'             => ['amount'],
        'available_budgets'        => ['amount'],
        'bills'                    => ['amount_min', 'amount_max'],
        'budget_limits'            => ['amount'],
        'currency_exchange_rates'  => ['rate', 'user_rate'],
        'limit_repetitions'        => ['amount'],
        'piggy_bank_events'        => ['amount'],
        'piggy_bank_repetitions'   => ['currentamount'],
        'piggy_banks'              => ['targetamount'],
        'recurrences_transactions' => ['amount', 'foreign_amount'],
        'transactions'             => ['amount', 'foreign_amount'],
    ];

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        Log::debug('Now in ForceDecimalSize::handle()');
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }

        $this->error('Running this command is dangerous and can cause data loss.');
        $this->error('Please do not continue.');
        $question = $this->confirm('Do you want to continue?');
        if (true === $question) {
            $user = $this->getUser();
            Log::channel('audit')->info(sprintf('User #%d ("%s") forced DECIMAL size.', $user->id, $user->email));
            $this->correctAmounts();
            $this->updateDecimals();
        }
        $this->line('Done!');
        return 0;
    }

    /**
     * This method loops over all accounts and validates the amounts.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctAccountAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = Account::leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
                        ->where('account_meta.name', 'currency_id')
                        ->where('account_meta.data', json_encode((string)$currency->id));
        $query->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(accounts.%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['accounts.*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All accounts in %s', $currency->code));
            return;
        }
        /** @var Account $account */
        foreach ($result as $account) {
            foreach ($fields as $field) {
                $value = $account->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Account #%d has %s with value "%s", this has been corrected to "%s".', $account->id, $field, $value, $correct));
                Account::find($account->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method checks if a basic check can be done or if it needs to be complicated.
     *
     * @return void
     */
    private function correctAmounts(): void
    {
        if ('mysql' !== config('database.default')) {
            $this->line('Skip correcting amounts...');
            return;
        }
        $this->correctAmountsByCurrency();
    }

    /**
     * This method fixes all auto budgets in currency $currency.
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctAutoBudgetAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = AutoBudget::where('transaction_currency_id', $currency->id)->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All auto budgets in %s', $currency->code));
            return;
        }
        /** @var AutoBudget $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Auto budget #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                AutoBudget::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method loops all enabled currencies and then calls the method that will fix all objects in this currency.
     *
     * @return void
     */
    private function correctAmountsByCurrency(): void
    {
        $this->line('Going to correct amounts, using the SLOW lane.');
        /** @var Collection $enabled */
        $enabled = TransactionCurrency::whereEnabled(1)->get();
        /** @var TransactionCurrency $currency */
        foreach ($enabled as $currency) {
            $this->correctByCurrency($currency);
        }
    }

    /**
     * This method fixes all available budgets in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctAvailableBudgetAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = AvailableBudget::where('transaction_currency_id', $currency->id)->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All available budgets in %s', $currency->code));
            return;
        }
        /** @var AvailableBudget $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Available budget #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                AvailableBudget::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all bills in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctBillAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = Bill::where('transaction_currency_id', $currency->id)->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All bills in %s', $currency->code));
            return;
        }
        /** @var Bill $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Bill #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                Bill::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all budget limits in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctBudgetLimitAmounts(TransactionCurrency $currency, array $fields)
    {
        /** @var Builder $query */
        $query = BudgetLimit::where('transaction_currency_id', $currency->id)->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All budget limits in %s', $currency->code));
            return;
        }
        /** @var BudgetLimit $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Budget limit #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                BudgetLimit::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method loops the available tables that may need fixing, and calls for the right method that can fix them.
     *
     * @param  TransactionCurrency  $currency
     * @return void
     * @throws FireflyException
     */
    private function correctByCurrency(TransactionCurrency $currency): void
    {
        $this->line(sprintf('Going to correct amounts in currency %s ("%s").', $currency->code, $currency->name));
        /**
         * @var string $name
         * @var array $fields
         */
        foreach ($this->tables as $name => $fields) {
            switch ($name) {
                default:
                    $message = sprintf('Cannot handle table "%s"', $name);
                    $this->line($message);
                    throw new FireflyException($message);
                    break;
                case 'accounts':
                    $this->correctAccountAmounts($currency, $fields);
                    break;
                case 'auto_budgets':
                    $this->correctAutoBudgetAmounts($currency, $fields);
                    break;
                case 'available_budgets':
                    $this->correctAvailableBudgetAmounts($currency, $fields);
                    break;
                case 'bills':
                    $this->correctBillAmounts($currency, $fields);
                    break;
                case 'budget_limits':
                    $this->correctBudgetLimitAmounts($currency, $fields);
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
                case 'recurrences_transactions':
                    $this->correctRecurringTransactionAmounts($currency, $fields);
                    break;
                case 'transactions':
                    $this->correctTransactionAmounts($currency);
                    break;
            }
        }
    }

    /**
     * This method fixes all piggy banks in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctPiggyAmounts(TransactionCurrency $currency, array $fields)
    {
        /** @var Builder $query */
        $query = PiggyBank::leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
                          ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
                          ->where('account_meta.name', 'currency_id')
                          ->where('account_meta.data', json_encode((string)$currency->id))
                          ->where(static function (Builder $q) use ($fields, $currency) {
                              foreach ($fields as $field) {
                                  $q->orWhere(
                                      DB::raw(sprintf('CAST(piggy_banks.%s AS CHAR)', $field)),
                                      'REGEXP',
                                      DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                                  );
                              }
                          });

        $result = $query->get(['piggy_banks.*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All piggy banks in %s', $currency->code));
            return;
        }
        /** @var PiggyBank $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Piggy bank #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                PiggyBank::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all piggy bank events in currency $currency.
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctPiggyEventAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = PiggyBankEvent::leftJoin('piggy_banks', 'piggy_bank_events.piggy_bank_id', '=', 'piggy_banks.id')
                               ->leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
                               ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
                               ->where('account_meta.name', 'currency_id')
                               ->where('account_meta.data', json_encode((string)$currency->id))
                               ->where(static function (Builder $q) use ($fields, $currency) {
                                   foreach ($fields as $field) {
                                       $q->orWhere(
                                           DB::raw(sprintf('CAST(piggy_bank_events.%s AS CHAR)', $field)),
                                           'REGEXP',
                                           DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                                       );
                                   }
                               });

        $result = $query->get(['piggy_bank_events.*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All piggy bank events in %s', $currency->code));
            return;
        }
        /** @var PiggyBankEvent $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Piggy bank event #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                PiggyBankEvent::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all piggy bank repetitions in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctPiggyRepetitionAmounts(TransactionCurrency $currency, array $fields)
    {
        // select all piggy bank repetitions with this currency and issue.
        /** @var Builder $query */
        $query = PiggyBankRepetition::leftJoin('piggy_banks', 'piggy_bank_repetitions.piggy_bank_id', '=', 'piggy_banks.id')
                                    ->leftJoin('accounts', 'piggy_banks.account_id', '=', 'accounts.id')
                                    ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
                                    ->where('account_meta.name', 'currency_id')
                                    ->where('account_meta.data', json_encode((string)$currency->id))
                                    ->where(static function (Builder $q) use ($fields, $currency) {
                                        foreach ($fields as $field) {
                                            $q->orWhere(
                                                DB::raw(sprintf('CAST(piggy_bank_repetitions.%s AS CHAR)', $field)),
                                                'REGEXP',
                                                DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                                            );
                                        }
                                    });

        $result = $query->get(['piggy_bank_repetitions.*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All piggy bank repetitions in %s', $currency->code));
            return;
        }
        /** @var PiggyBankRepetition $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Piggy bank repetition #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                PiggyBankRepetition::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all recurring transactions  in currency $currency.
     * @param  TransactionCurrency  $currency
     * @param  array  $fields
     * @return void
     */
    private function correctRecurringTransactionAmounts(TransactionCurrency $currency, array $fields): void
    {
        /** @var Builder $query */
        $query = RecurrenceTransaction::where('transaction_currency_id', $currency->id)->where(static function (Builder $q) use ($fields, $currency) {
            foreach ($fields as $field) {
                $q->orWhere(
                    DB::raw(sprintf('CAST(%s AS CHAR)', $field)),
                    'REGEXP',
                    DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
                );
            }
        });

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All recurring transactions in %s', $currency->code));
            return;
        }
        /** @var RecurrenceTransaction $item */
        foreach ($result as $item) {
            foreach ($fields as $field) {
                $value = $item->$field;
                if (null === $value) {
                    continue;
                }
                // fix $field by rounding it down correctly.
                $pow     = pow(10, (int)$currency->decimal_places);
                $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
                $this->line(sprintf('Recurring transaction #%d has %s with value "%s", this has been corrected to "%s".', $item->id, $field, $value, $correct));
                RecurrenceTransaction::find($item->id)->update([$field => $correct]);
            }
        }
    }

    /**
     * This method fixes all transactions in currency $currency.
     *
     * @param  TransactionCurrency  $currency
     * @return void
     */
    private function correctTransactionAmounts(TransactionCurrency $currency): void
    {
        // select all transactions with this currency and issue.
        /** @var Builder $query */
        $query = Transaction::where('transaction_currency_id', $currency->id)->where(
            DB::raw('CAST(amount AS CHAR)'),
            'REGEXP',
            DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
        );

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All transactions in %s', $currency->code));
        }
        /** @var Transaction $item */
        foreach ($result as $item) {
            $value = $item->amount;
            if (null === $value) {
                continue;
            }
            // fix $field by rounding it down correctly.
            $pow     = pow(10, (int)$currency->decimal_places);
            $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
            $this->line(sprintf('Transaction #%d has amount with value "%s", this has been corrected to "%s".', $item->id, $value, $correct));
            Transaction::find($item->id)->update(['amount' => $correct]);
        }

        // select all transactions with this FOREIGN currency and issue.
        /** @var Builder $query */
        $query = Transaction::where('foreign_currency_id', $currency->id)->where(
            DB::raw('CAST(foreign_amount AS CHAR)'),
            'REGEXP',
            DB::raw(sprintf('\'\\\\.[\\\\d]{%d}[1-9]+\'', $currency->decimal_places))
        );

        $result = $query->get(['*']);
        if (0 === $result->count()) {
            $this->line(sprintf('Correct: All transactions in foreign currency %s', $currency->code));
            return;
        }
        /** @var Transaction $item */
        foreach ($result as $item) {
            $value = $item->foreign_amount;
            if (null === $value) {
                continue;
            }
            // fix $field by rounding it down correctly.
            $pow     = pow(10, (int)$currency->decimal_places);
            $correct = bcdiv((string)round($value * $pow), (string)$pow, 12);
            $this->line(sprintf('Transaction #%d has foreign amount with value "%s", this has been corrected to "%s".', $item->id, $value, $correct));
            Transaction::find($item->id)->update(['foreign_amount' => $correct]);
        }
    }

    /**
     * @return void
     */
    private function updateDecimals(): void
    {
        $this->info('Going to force the size of DECIMAL columns. Please hold.');

        /**
         * @var string $name
         * @var array $fields
         */
        foreach ($this->tables as $name => $fields) {
            /** @var string $field */
            foreach ($fields as $field) {
                $this->line(sprintf('Updating table "%s", field "%s"...', $name, $field));
                $query = sprintf('ALTER TABLE %s CHANGE COLUMN %s %s DECIMAL(32, 12);', $name, $field, $field);
                DB::select($query);
                sleep(1);
            }
        }
    }
}
