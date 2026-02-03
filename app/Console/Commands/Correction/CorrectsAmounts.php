<?php

/*
 * CorrectAmounts.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\GenericDestroyService;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ValueError;

class CorrectsAmounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'This command makes sure positive and negative amounts are recorded correctly.';
    protected $signature   = 'correction:amounts';
    private JournalDestroyService $service;
    private GenericDestroyService $genericService;

    public function handle(): int
    {
        $this->service = new JournalDestroyService();
        $this->genericService = new GenericDestroyService();
        // transfers must not have foreign currency info if both accounts have the same currency.
        $this->correctTransfers();
        // auto budgets must be positive
        $this->fixAutoBudgets();
        // available budgets must be positive
        $this->fixAvailableBudgets();
        // bills must be positive (both amounts)
        $this->fixBills();
        // budget limits must be positive
        $this->fixBudgetLimits();
        // currency_exchange_rates must be positive
        $this->fixExchangeRates();
        // piggy_banks must be positive
        $this->fixPiggyBanks();
        // recurrences_transactions amount must be positive
        $this->fixRecurrences();
        // rule_triggers must be positive or zero (amount_less, amount_more, amount_is)
        $this->fixRuleTriggers();

        return 0;
    }

    private function correctTransfers(): void
    {
        Log::debug('Will now correct transfers.');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $type       = TransactionType::where('type', TransactionTypeEnum::TRANSFER->value)->first();
        $journals   = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->whereNotNull('transactions.foreign_amount')
            ->where('transaction_journals.transaction_type_id', $type->id)
            ->distinct()
            ->get(['transaction_journals.*'])
        ;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $repository->setUser($journal->user);
            $primary        = Amount::getPrimaryCurrencyByUserGroup($journal->userGroup);

            $valid          = $this->validateJournal($journal);
            if (false === $valid) {
                // Log::debug(sprintf('Journal #%d does not need to be fixed or is invalid (see previous messages)', $journal->id));

                continue;
            }
            Log::debug(sprintf('Journal #%d is ready to be corrected (if necessary).', $journal->id));
            $source         = $journal->transactions()->where('amount', '<', '0')->first();
            $destination    = $journal->transactions()->where('amount', '>', '0')->first();
            $sourceAccount  = $source->account;
            $destAccount    = $destination->account;
            $sourceCurrency = $repository->getAccountCurrency($sourceAccount) ?? $primary;
            $destCurrency   = $repository->getAccountCurrency($destAccount) ?? $primary;
            Log::debug(sprintf('Currency of source account      #%d "%s" is %s', $sourceAccount->id, $sourceAccount->name, $sourceCurrency->code));
            Log::debug(sprintf('Currency of destination account #%d "%s" is %s', $destAccount->id, $destAccount->name, $destCurrency->code));

            if ($sourceCurrency->id === $destCurrency->id) {
                Log::debug('Both accounts have the same currency. Removing foreign currency info.');
                $source->foreign_currency_id      = null;
                $source->foreign_amount           = null;
                $source->save();
                $destination->foreign_currency_id = null;
                $destination->foreign_amount      = null;
                $destination->save();

                continue;
            }

            // validate source transaction
            if ($destCurrency->id !== $source->foreign_currency_id) {
                Log::debug(sprintf(
                    '[a] Journal #%d: transaction #%d refers to foreign currency "%s" but should refer to "%s".',
                    $journal->id,
                    $source->id,
                    $source->foreignCurrency->code,
                    $destCurrency->code
                ));
                $source->foreign_currency_id = $destCurrency->id;
                $source->save();
            }
            if ($sourceCurrency->id !== $source->transaction_currency_id) {
                Log::debug(sprintf(
                    '[b] Journal #%d: transaction #%d refers to currency "%s" but should refer to "%s".',
                    $journal->id,
                    $source->id,
                    $source->transactionCurrency->code,
                    $sourceCurrency->code
                ));
                $source->transaction_currency_id = $sourceCurrency->id;
                $source->save();
            }

            // validate destination:
            if ($sourceCurrency->id !== $destination->foreign_currency_id) {
                Log::debug(sprintf(
                    '[c] Journal #%d: transaction #%d refers to foreign currency "%s" but should refer to "%s".',
                    $journal->id,
                    $destination->id,
                    $destination->foreignCurrency->code,
                    $sourceCurrency->code
                ));
                $destination->foreign_currency_id = $sourceCurrency->id;
                $destination->save();
            }

            if ($destCurrency->id !== $destination->transaction_currency_id) {
                Log::debug(sprintf(
                    '[d] Journal #%d: transaction #%d refers to currency "%s" but should refer to "%s".',
                    $journal->id,
                    $destination->id,
                    $destination->transactionCurrency->code,
                    $destCurrency->code
                ));
                $destination->transaction_currency_id = $destCurrency->id;
                $destination->save();
            }
            Log::debug(sprintf('Done with journal #%d.', $journal->id));
        }
    }

    private function deleteJournal(TransactionJournal $journal): void
    {
        $this->service->destroy($journal);
    }

    private function fixAutoBudgets(): void
    {
        $count = AutoBudget::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d auto budget amount(s).', $count));
    }

    private function fixAvailableBudgets(): void
    {
        $count = AvailableBudget::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d available budget amount(s).', $count));
    }

    private function fixBills(): void
    {
        $count = 0;
        $count += Bill::where('amount_max', '<', 0)->update(['amount_max' => DB::raw('amount_max * -1')]);
        $count += Bill::where('amount_min', '<', 0)->update(['amount_min' => DB::raw('amount_min * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d bill amount(s).', $count));
    }

    private function fixBudgetLimits(): void
    {
        $count = BudgetLimit::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d budget limit amount(s).', $count));
    }

    private function fixExchangeRates(): void
    {
        $count = CurrencyExchangeRate::where('rate', '<', 0)->update(['rate' => DB::raw('rate * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d currency exchange rate(s).', $count));
    }

    private function fixPiggyBanks(): void
    {
        $count = PiggyBank::where('target_amount', '<', 0)->update(['target_amount' => DB::raw('target_amount * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d piggy bank amount(s).', $count));
    }

    private function fixRecurrences(): void
    {
        $count = 0;
        $count += RecurrenceTransaction::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        $count += RecurrenceTransaction::where('foreign_amount', '<', 0)->update(['foreign_amount' => DB::raw('foreign_amount * -1')]);
        if (0 === $count) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d recurring transaction amount(s).', $count));
    }

    /**
     * Foreach loop is unavoidable here.
     */
    private function fixRuleTriggers(): void
    {
        $set   = RuleTrigger::whereIn('trigger_type', ['amount_less', 'amount_more', 'amount_is'])->get();
        $fixed = 0;

        /** @var RuleTrigger $item */
        foreach ($set as $item) {
            $result = $this->fixRuleTrigger($item);
            if ($result) {
                ++$fixed;
            }
        }
        if (0 === $fixed) {
            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d rule trigger amount(s).', $fixed));
    }

    private function fixRuleTrigger(RuleTrigger $item): bool
    {
        try {
            $check = bccomp((string) $item->trigger_value, '0');
        } catch (ValueError) {
            $this->friendlyError(sprintf(
                'Rule #%d contained invalid %s-trigger "%s". The trigger has been removed, and the rule is disabled.',
                $item->rule_id,
                $item->trigger_type,
                $item->trigger_value
            ));
            $item->rule->active = false;
            $item->rule->save();
            $this->genericService->deleteRuleTrigger($item);

            return false;
        }
        if (-1 === $check) {
            $item->trigger_value = Steam::positive($item->trigger_value);
            $item->save();

            return true;
        }

        return false;
    }

    private function validateJournal(TransactionJournal $journal): bool
    {
        $countSource   = $journal->transactions()->where('amount', '<', 0)->count();
        $countDest     = $journal->transactions()->where('amount', '>', 0)->count();

        if (1 !== $countSource || 1 !== $countDest) {
            $this->friendlyError(sprintf('Transaction journal #%d has bad transaction information. Will delete.', $journal->id));
            $this->deleteJournal($journal);
            Log::error(sprintf('Transaction journal #%d has bad transaction information. Will delete.', $journal->id));

            return false;
        }

        /** @var null|Transaction $source */
        $source        = $journal->transactions()->where('amount', '<', 0)->first();

        /** @var null|Transaction $destination */
        $destination   = $journal->transactions()->where('amount', '>', 0)->first();

        if (null === $source || null === $destination) {
            $this->friendlyError(sprintf('Could not find source OR destination for journal #%d .', $journal->id));
            Log::error(sprintf('Could not find source OR destination for journal #%d .', $journal->id));
            $this->deleteJournal($journal);

            return false;
        }
        if (null === $source->foreign_currency_id || null === $destination->foreign_currency_id) {
            // Log::debug('No foreign currency information is present, can safely continue with other transactions.');

            return false;
        }
        if (null === $source->foreign_amount || null === $destination->foreign_amount) {
            $this->friendlyError(sprintf('Transactions of journal #%d have no foreign amount, but have foreign currency info. Will reset this.', $journal->id));
            $source->foreign_currency_id      = null;
            $source->save();
            $destination->foreign_currency_id = null;
            $source->save();

            return false;
        }

        $sourceAccount = $source->account;
        $destAccount   = $destination->account;
        if (null === $sourceAccount || null === $destAccount) {
            $this->friendlyError(sprintf('Could not find accounts for journal #%d,', $journal->id));
            $this->deleteJournal($journal);

            return false;
        }

        return true;
    }
}
