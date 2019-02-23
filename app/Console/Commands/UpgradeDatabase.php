<?php

/**
 * UpgradeDatabase.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection PhpStaticAsDynamicMethodCallInspection */
/** @noinspection PhpDynamicAsStaticMethodCallInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use DB;
use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Note;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;
use Schema;
use UnexpectedValueException;

/**
 * Class UpgradeDatabase.
 *
 * Upgrade user database.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @codeCoverageIgnore
 */
class UpgradeDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will run various commands to update database records.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:upgrade-database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->setTransactionIdentifier();
        $this->updateAccountCurrencies();
        $this->createNewTypes();
        $this->line('Updating currency information..');
        $this->updateTransferCurrencies();
        $this->updateOtherCurrencies();
        $this->line('Done updating currency information..');
        $this->migrateNotes();
        $this->migrateAttachmentData();
        $this->migrateBillsToRules();
        $this->budgetLimitCurrency();
        $this->removeCCLiabilities();

        $this->info('Firefly III database is up to date.');

        return 0;
    }

    /**
     * Since it is one routine these warnings make sense and should be supressed.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function migrateBillsToRules(): void
    {
        foreach (User::get() as $user) {
            /** @var Preference $lang */
            $lang               = app('preferences')->getForUser($user, 'language', 'en_US');
            $groupName          = (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data);
            $ruleGroup          = $user->ruleGroups()->where('title', $groupName)->first();
            $currencyPreference = app('preferences')->getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));

            if (null === $currencyPreference) {
                $this->error('User has no currency preference. Impossible.');

                return;
            }

            $currency = TransactionCurrency::where('code', $currencyPreference->data)->first();
            if (null === $currency) {
                $this->line('Fall back to default currency in migrateBillsToRules().');
                $currency = app('amount')->getDefaultCurrencyByUser($user);
            }

            if (null === $ruleGroup) {
                $array     = RuleGroup::get(['order'])->pluck('order')->toArray();
                $order     = \count($array) > 0 ? max($array) + 1 : 1;
                $ruleGroup = RuleGroup::create(
                    [
                        'user_id'     => $user->id,
                        'title'       => (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data),
                        'description' => (string)trans('firefly.rulegroup_for_bills_description', [], $lang->data),
                        'order'       => $order,
                        'active'      => 1,
                    ]
                );
            }

            // loop bills.
            $order = 1;
            /** @var Collection $collection */
            $collection = $user->bills()->get();
            /** @var Bill $bill */
            foreach ($collection as $bill) {
                if ('MIGRATED_TO_RULES' !== $bill->match) {
                    $rule = Rule::create(
                        [
                            'user_id'         => $user->id,
                            'rule_group_id'   => $ruleGroup->id,
                            'title'           => (string)trans('firefly.rule_for_bill_title', ['name' => $bill->name], $lang->data),
                            'description'     => (string)trans('firefly.rule_for_bill_description', ['name' => $bill->name], $lang->data),
                            'order'           => $order,
                            'active'          => $bill->active,
                            'stop_processing' => 1,
                        ]
                    );
                    // add default trigger
                    RuleTrigger::create(
                        [
                            'rule_id'         => $rule->id,
                            'trigger_type'    => 'user_action',
                            'trigger_value'   => 'store-journal',
                            'active'          => 1,
                            'stop_processing' => 0,
                            'order'           => 1,
                        ]
                    );
                    // add trigger for description
                    $match = implode(' ', explode(',', $bill->match));
                    RuleTrigger::create(
                        [
                            'rule_id'         => $rule->id,
                            'trigger_type'    => 'description_contains',
                            'trigger_value'   => $match,
                            'active'          => 1,
                            'stop_processing' => 0,
                            'order'           => 2,
                        ]
                    );
                    if ($bill->amount_max !== $bill->amount_min) {
                        // add triggers for amounts:
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_less',
                                'trigger_value'   => round($bill->amount_max, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 3,
                            ]
                        );
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_more',
                                'trigger_value'   => round((float)$bill->amount_min, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 4,
                            ]
                        );
                    }
                    if ($bill->amount_max === $bill->amount_min) {
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_exactly',
                                'trigger_value'   => round((float)$bill->amount_min, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 3,
                            ]
                        );
                    }

                    // create action
                    RuleAction::create(
                        [
                            'rule_id'         => $rule->id,
                            'action_type'     => 'link_to_bill',
                            'action_value'    => $bill->name,
                            'order'           => 1,
                            'active'          => 1,
                            'stop_processing' => 0,
                        ]
                    );

                    $order++;
                    $bill->match = 'MIGRATED_TO_RULES';
                    $bill->save();
                    $this->line(sprintf('Updated bill #%d ("%s") so it will use rules.', $bill->id, $bill->name));
                }

                // give bills a currency when they dont have one.
                if (null === $bill->transaction_currency_id) {
                    $this->line(sprintf('Gave bill #%d ("%s") a currency (%s).', $bill->id, $bill->name, $currency->name));
                    $bill->transactionCurrency()->associate($currency);
                    $bill->save();
                }
            }
        }
    }

    /**
     * This method gives all transactions which are part of a split journal (so more than 2) a sort of "order" so they are easier
     * to easier to match to their counterpart. When a journal is split, it has two or three transactions: -3, -4 and -5 for example.
     *
     * In the database this is reflected as 6 transactions: -3/+3, -4/+4, -5/+5.
     *
     * When either of these are the same amount, FF3 can't keep them apart: +3/-3, +3/-3, +3/-3. This happens more often than you would
     * think. So each set gets a number (1,2,3) to keep them apart.
     */
    public function setTransactionIdentifier(): void
    {
        // if table does not exist, return false
        if (!Schema::hasTable('transaction_journals')) {
            return;
        }
        $subQuery = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                      ->whereNull('transaction_journals.deleted_at')
                                      ->whereNull('transactions.deleted_at')
                                      ->groupBy(['transaction_journals.id'])
                                      ->select(['transaction_journals.id', DB::raw('COUNT(transactions.id) AS t_count')]);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $result     = DB::table(DB::raw('(' . $subQuery->toSql() . ') AS derived'))
                        ->mergeBindings($subQuery->getQuery())
                        ->where('t_count', '>', 2)
                        ->select(['id', 't_count']);
        $journalIds = array_unique($result->pluck('id')->toArray());

        foreach ($journalIds as $journalId) {
            $this->updateJournalidentifiers((int)$journalId);
        }

    }

    /**
     * Each (asset) account must have a reference to a preferred currency. If the account does not have one, it's forced upon the account.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateAccountCurrencies(): void
    {
        Log::debug('Now in updateAccountCurrencies()');

        $defaultConfig = (string)config('firefly.default_currency', 'EUR');
        Log::debug(sprintf('System default currency is "%s"', $defaultConfig));

        $accounts = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->get(['accounts.*']);
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts->each(
            function (Account $account) use ($repository, $defaultConfig) {
                $repository->setUser($account->user);
                // get users preference, fall back to system pref.

                // expand and debug routine.
                $defaultCurrencyCode = app('preferences')->getForUser($account->user, 'currencyPreference', $defaultConfig)->data;
                Log::debug(sprintf('Default currency code is "%s"', var_export($defaultCurrencyCode, true)));
                if (!is_string($defaultCurrencyCode)) {
                    $defaultCurrencyCode = $defaultConfig;
                    Log::debug(sprintf('Default currency code is not a string, now set to "%s"', $defaultCurrencyCode));
                }
                $defaultCurrency = TransactionCurrency::where('code', $defaultCurrencyCode)->first();
                $accountCurrency = (int)$repository->getMetaValue($account, 'currency_id');
                $openingBalance  = $account->getOpeningBalance();
                $obCurrency      = (int)$openingBalance->transaction_currency_id;

                if (null === $defaultCurrency) {
                    throw new UnexpectedValueException(sprintf('User has a preference for "%s", but this currency does not exist.', $defaultCurrencyCode));
                }
                Log::debug(
                    sprintf('Found default currency #%d (%s) while searching for "%s"', $defaultCurrency->id, $defaultCurrency->code, $defaultCurrencyCode)
                );

                // both 0? set to default currency:
                if (0 === $accountCurrency && 0 === $obCurrency) {
                    AccountMeta::where('account_id', $account->id)->where('name', 'currency_id')->forceDelete();
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $defaultCurrency->id]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // account is set to 0, opening balance is not?
                if (0 === $accountCurrency && $obCurrency > 0) {
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $obCurrency]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // do not match and opening balance id is not null.
                if ($accountCurrency !== $obCurrency && $openingBalance->id > 0) {
                    // update opening balance:
                    $openingBalance->transaction_currency_id = $accountCurrency;
                    $openingBalance->save();
                    $this->line(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));

                    return true;
                }

                return true;
            }
        );

    }

    /**
     * This routine verifies that withdrawals, deposits and opening balances have the correct currency settings for
     * the accounts they are linked to.
     *
     * Both source and destination must match the respective currency preference of the related asset account.
     * So FF3 must verify all transactions.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateOtherCurrencies(): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $set          = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $journal) use ($repository, $accountRepos) {
                // get the transaction with the asset account in it:
                /** @var Transaction $transaction */
                $transaction = $journal->transactions()
                                       ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                       ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                       ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->first(['transactions.*']);
                if (null === $transaction) {
                    return;
                }
                $accountRepos->setUser($journal->user);
                /** @var Account $account */
                $account  = $transaction->account;
                $currency = $repository->findNull((int)$accountRepos->getMetaValue($account, 'currency_id'));
                if (null === $currency) {
                    return;
                }
                $transactions = $journal->transactions()->get();
                $transactions->each(
                    function (Transaction $transaction) use ($currency) {
                        if (null === $transaction->transaction_currency_id) {
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                        }

                        // when mismatch in transaction:
                        if (!((int)$transaction->transaction_currency_id === (int)$currency->id)) {
                            $transaction->foreign_currency_id     = (int)$transaction->transaction_currency_id;
                            $transaction->foreign_amount          = $transaction->amount;
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                        }
                    }
                );
                // also update the journal, of course:
                $journal->transaction_currency_id = $currency->id;
                $journal->save();
            }
        );

    }

    /**
     * This routine verifies that transfers have the correct currency settings for the accounts they are linked to.
     * For transfers, this is can be a destructive routine since we FORCE them into a currency setting whether they
     * like it or not. Previous routines MUST have set the currency setting for both accounts for this to work.
     *
     * A transfer always has the
     *
     * Both source and destination must match the respective currency preference. So FF3 must verify ALL
     * transactions.
     */
    public function updateTransferCurrencies(): void
    {
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionType::TRANSFER)
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $transfer) {
                // select all "source" transactions:
                /** @var Collection $transactions */
                $transactions = $transfer->transactions()->where('amount', '<', 0)->get();
                $transactions->each(
                    function (Transaction $transaction) {
                        $this->updateTransactionCurrency($transaction);
                        $this->updateJournalCurrency($transaction);
                    }
                );
            }
        );
    }

    /**
     *
     */
    private function budgetLimitCurrency(): void
    {
        $budgetLimits = BudgetLimit::get();
        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            if (null === $budgetLimit->transaction_currency_id) {
                /** @var Budget $budget */
                $budget = $budgetLimit->budget;
                if (null !== $budget) {
                    $user = $budget->user;
                    if (null !== $user) {
                        $currency                             = app('amount')->getDefaultCurrencyByUser($user);
                        $budgetLimit->transaction_currency_id = $currency->id;
                        $budgetLimit->save();
                        $this->line(
                            sprintf('Budget limit #%d (part of budget "%s") now has a currency setting (%s).', $budgetLimit->id, $budget->name, $currency->name)
                        );
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function createNewTypes(): void
    {
        // create transaction type "Reconciliation".
        $type = TransactionType::where('type', TransactionType::RECONCILIATION)->first();
        if (null === $type) {
            TransactionType::create(['type' => TransactionType::RECONCILIATION]);
        }
        $account = AccountType::where('type', AccountType::RECONCILIATION)->first();
        if (null === $account) {
            AccountType::create(['type' => AccountType::RECONCILIATION]);
        }
    }

    /**
     * Move the description of each attachment (when not NULL) to the notes or to a new note object
     * for all attachments.
     */
    private function migrateAttachmentData(): void
    {
        $attachments = Attachment::get();

        /** @var Attachment $att */
        foreach ($attachments as $att) {

            // move description:
            $description = (string)$att->description;
            if ('' !== $description) {
                // find or create note:
                $note = $att->notes()->first();
                if (null === $note) {
                    $note = new Note;
                    $note->noteable()->associate($att);
                }
                $note->text = $description;
                $note->save();

                // clear description:
                $att->description = '';
                $att->save();

                Log::debug(sprintf('Migrated attachment #%s description to note #%d', $att->id, $note->id));
            }
        }
    }

    /**
     * Move all the journal_meta notes to their note object counter parts.
     */
    private function migrateNotes(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $set = TransactionJournalMeta::whereName('notes')->get();
        /** @var TransactionJournalMeta $meta */
        foreach ($set as $meta) {
            $journal = $meta->transactionJournal;
            $note    = $journal->notes()->first();
            if (null === $note) {
                $note = new Note();
                $note->noteable()->associate($journal);
            }

            $note->text = $meta->data;
            $note->save();
            Log::debug(sprintf('Migrated meta note #%d to Note #%d', $meta->id, $note->id));
            try {
                $meta->delete();
            } catch (Exception $e) {
                Log::error(sprintf('Could not delete old meta entry #%d: %s', $meta->id, $e->getMessage()));
            }
        }
    }

    /**
     *
     */
    private function removeCCLiabilities(): void
    {
        $ccType   = AccountType::where('type', AccountType::CREDITCARD)->first();
        $debtType = AccountType::where('type', AccountType::DEBT)->first();
        if (null === $ccType || null === $debtType) {
            return;
        }
        /** @var Collection $accounts */
        $accounts = Account::where('account_type_id', $ccType->id)->get();
        foreach ($accounts as $account) {
            $account->account_type_id = $debtType->id;
            $account->save();
            $this->line(sprintf('Converted credit card liability account "%s" (#%d) to generic debt liability.', $account->name, $account->id));
        }
        if ($accounts->count() > 0) {
            $this->info('Credit card liability types are no longer supported and have been converted to generic debts. See: http://bit.ly/FF3-credit-cards');
        }
    }

    /**
     * This method makes sure that the transaction journal uses the currency given in the transaction.
     *
     * @param Transaction $transaction
     */
    private function updateJournalCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($transaction->account->user);
        $currency = $repository->findNull((int)$accountRepos->getMetaValue($transaction->account, 'currency_id'));
        $journal  = $transaction->transactionJournal;

        if (null === $currency) {
            return;
        }

        if (!((int)$currency->id === (int)$journal->transaction_currency_id)) {
            $this->line(
                sprintf(
                    'Transfer #%d ("%s") has been updated to use %s instead of %s.',
                    $journal->id,
                    $journal->description,
                    $currency->code,
                    $journal->transactionCurrency->code
                )
            );
            $journal->transaction_currency_id = $currency->id;
            $journal->save();
        }

    }

    /**
     * grab all positive transactiosn from this journal that are not deleted. for each one, grab the negative opposing one
     * which has 0 as an identifier and give it the same identifier.
     *
     * @param int $journalId
     */
    private function updateJournalidentifiers(int $journalId): void
    {
        $identifier   = 0;
        $processed    = [];
        $transactions = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // find opposing:
            $amount = bcmul((string)$transaction->amount, '-1');

            try {
                /** @var Transaction $opposing */
                $opposing = Transaction::where('transaction_journal_id', $journalId)
                                       ->where('amount', $amount)->where('identifier', '=', 0)
                                       ->whereNotIn('id', $processed)
                                       ->first();
            } catch (QueryException $e) {
                Log::error($e->getMessage());
                $this->error('Firefly III could not find the "identifier" field in the "transactions" table.');
                $this->error(sprintf('This field is required for Firefly III version %s to run.', config('firefly.version')));
                $this->error('Please run "php artisan migrate" to add this field to the table.');
                $this->info('Then, run "php artisan firefly:upgrade-database" to try again.');

                return;
            }
            if (null !== $opposing) {
                // give both a new identifier:
                $transaction->identifier = $identifier;
                $opposing->identifier    = $identifier;
                $transaction->save();
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
            }
            ++$identifier;
        }

    }

    /**
     * This method makes sure that the tranaction uses the same currency as the source account does.
     * If not, the currency is updated to include a reference to its original currency as the "foreign" currency.
     *
     * The transaction that is sent to this function MUST be the source transaction (amount negative).
     *
     * Method is long and complex but I'll allow it. https://imgur.com/gallery/dVDJiez
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param Transaction $transaction
     */
    private function updateTransactionCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos = app(JournalRepositoryInterface::class);

        $accountRepos->setUser($transaction->account->user);
        $journalRepos->setUser($transaction->account->user);
        $currency = $repository->findNull((int)$accountRepos->getMetaValue($transaction->account, 'currency_id'));

        if (null === $currency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $transaction->account->id, $transaction->account->name));

            return;
        }

        // has no currency ID? Must have, so fill in using account preference:
        if (null === $transaction->transaction_currency_id) {
            $transaction->transaction_currency_id = (int)$currency->id;
            Log::debug(sprintf('Transaction #%d has no currency setting, now set to %s', $transaction->id, $currency->code));
            $transaction->save();
        }

        // does not match the source account (see above)? Can be fixed
        // when mismatch in transaction and NO foreign amount is set:
        if (!((int)$transaction->transaction_currency_id === (int)$currency->id) && null === $transaction->foreign_amount) {
            Log::debug(
                sprintf(
                    'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                    $transaction->id,
                    $transaction->transaction_currency_id,
                    $currency->id,
                    $transaction->amount
                )
            );
            $transaction->transaction_currency_id = (int)$currency->id;
            $transaction->save();
        }

        // grab opposing transaction:
        /** @var TransactionJournal $journal */
        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposing */
        $opposing         = $journal->transactions()->where('amount', '>', 0)->where('identifier', $transaction->identifier)->first();
        $opposingCurrency = $repository->findNull((int)$accountRepos->getMetaValue($opposing->account, 'currency_id'));

        if (null === $opposingCurrency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $opposing->account->id, $opposing->account->name));

            return;
        }

        // if the destination account currency is the same, both foreign_amount and foreign_currency_id must be NULL for both transactions:
        if ((int)$opposingCurrency->id === (int)$currency->id) {
            // update both transactions to match:
            $transaction->foreign_amount       = null;
            $transaction->foreign_currency_id  = null;
            $opposing->foreign_amount          = null;
            $opposing->foreign_currency_id     = null;
            $opposing->transaction_currency_id = $currency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(
                sprintf(
                    'Currency for account "%s" is %s, and currency for account "%s" is also
             %s, so %s #%d (#%d and #%d) has been verified to be to %s exclusively.',
                    $opposing->account->name, $opposingCurrency->code,
                    $transaction->account->name, $transaction->transactionCurrency->code,
                    $journal->transactionType->type, $journal->id,
                    $transaction->id, $opposing->id, $currency->code
                )
            );

            return;
        }
        // if destination account currency is different, both transactions must have this currency as foreign currency id.
        if (!((int)$opposingCurrency->id === (int)$currency->id)) {
            $transaction->foreign_currency_id = $opposingCurrency->id;
            $opposing->foreign_currency_id    = $opposingCurrency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(sprintf('Verified foreign currency ID of transaction #%d and #%d', $transaction->id, $opposing->id));
        }

        // if foreign amount of one is null and the other is not, use this to restore:
        if (null === $transaction->foreign_amount && null !== $opposing->foreign_amount) {
            $transaction->foreign_amount = bcmul((string)$opposing->foreign_amount, '-1');
            $transaction->save();
            Log::debug(sprintf('Restored foreign amount of transaction (1) #%d to %s', $transaction->id, $transaction->foreign_amount));
        }

        // if foreign amount of one is null and the other is not, use this to restore (other way around)
        if (null === $opposing->foreign_amount && null !== $transaction->foreign_amount) {
            $opposing->foreign_amount = bcmul((string)$transaction->foreign_amount, '-1');
            $opposing->save();
            Log::debug(sprintf('Restored foreign amount of transaction (2) #%d to %s', $opposing->id, $opposing->foreign_amount));
        }

        // when both are zero, try to grab it from journal:
        if (null === $opposing->foreign_amount && null === $transaction->foreign_amount) {
            $foreignAmount = $journalRepos->getMetaField($journal, 'foreign_amount');
            if (null === $foreignAmount) {
                Log::debug(sprintf('Journal #%d has missing foreign currency data, forced to do 1:1 conversion :(.', $transaction->transaction_journal_id));
                $transaction->foreign_amount = bcmul((string)$transaction->amount, '-1');
                $opposing->foreign_amount    = bcmul((string)$opposing->amount, '-1');
                $transaction->save();
                $opposing->save();

                return;
            }
            $foreignPositive = app('steam')->positive((string)$foreignAmount);
            Log::debug(
                sprintf(
                    'Journal #%d has missing foreign currency info, try to restore from meta-data ("%s").',
                    $transaction->transaction_journal_id,
                    $foreignAmount
                )
            );
            $transaction->foreign_amount = bcmul($foreignPositive, '-1');
            $opposing->foreign_amount    = $foreignPositive;
            $transaction->save();
            $opposing->save();
        }

    }

}
