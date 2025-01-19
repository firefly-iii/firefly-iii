<?php

/**
 * ExportDataGenerator.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Export;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\User;
use Illuminate\Support\Collection;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;

/**
 * Class ExportDataGenerator
 */
class ExportDataGenerator
{
    use ConvertsDataTypes;

    private const string ADD_RECORD_ERR = 'Could not add record to set: %s';
    private const string EXPORT_ERR     = 'Could not export to string: %s';
    private Collection $accounts;
    private Carbon     $end;
    private bool       $exportAccounts;
    private bool       $exportBills;
    private bool       $exportBudgets;
    private bool       $exportCategories;
    private bool       $exportPiggies;
    private bool       $exportRecurring;
    private bool       $exportRules;
    private bool       $exportTags;
    private bool       $exportTransactions;
    private Carbon     $start;
    private User       $user;

    public function __construct()
    {
        $this->accounts           = new Collection();
        $this->start              = today(config('app.timezone'));
        $this->start->subYear();
        $this->end                = today(config('app.timezone'));
        $this->exportTransactions = false;
        $this->exportAccounts     = false;
        $this->exportBudgets      = false;
        $this->exportCategories   = false;
        $this->exportTags         = false;
        $this->exportRecurring    = false;
        $this->exportRules        = false;
        $this->exportBills        = false;
        $this->exportPiggies      = false;
    }

    /**
     * @throws FireflyException
     */
    public function export(): array
    {
        $return = [];
        if ($this->exportAccounts) {
            $return['accounts'] = $this->exportAccounts();
        }
        if ($this->exportBills) {
            $return['bills'] = $this->exportBills();
        }
        if ($this->exportBudgets) {
            $return['budgets'] = $this->exportBudgets();
        }
        if ($this->exportCategories) {
            $return['categories'] = $this->exportCategories();
        }
        if ($this->exportPiggies) {
            $return['piggies'] = $this->exportPiggies();
        }
        if ($this->exportRecurring) {
            $return['recurrences'] = $this->exportRecurring();
        }
        if ($this->exportRules) {
            $return['rules'] = $this->exportRules();
        }
        if ($this->exportTags) {
            $return['tags'] = $this->exportTags();
        }
        if ($this->exportTransactions) {
            $return['transactions'] = $this->exportTransactions();
        }

        return $return;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportAccounts(): string
    {
        $header      = [
            'user_id',
            'account_id',
            'created_at',
            'updated_at',
            'type',
            'name',
            'virtual_balance',
            'iban',
            'number',
            'active',
            'currency_code',
            'role',
            'cc_type',
            'cc_payment_date',
            'in_net_worth',
            'interest',
            'interest_period',
        ];

        /** @var AccountRepositoryInterface $repository */
        $repository  = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);
        $allAccounts = $repository->getAccountsByType([]);
        $records     = [];

        /** @var Account $account */
        foreach ($allAccounts as $account) {
            $currency  = $repository->getAccountCurrency($account);
            $records[] = [
                $this->user->id,
                $account->id,
                $account->created_at->toAtomString(),
                $account->updated_at->toAtomString(),
                $account->accountType->type,
                $account->name,
                $account->virtual_balance,
                $account->iban,
                $account->account_number,
                $account->active,
                $currency?->code,
                $repository->getMetaValue($account, 'account_role'),
                $repository->getMetaValue($account, 'cc_type'),
                $repository->getMetaValue($account, 'cc_monthly_payment_date'),
                $repository->getMetaValue($account, 'include_net_worth'),
                $repository->getMetaValue($account, 'interest'),
                $repository->getMetaValue($account, 'interest_period'),
            ];
        }

        // load the CSV document from a string
        $csv         = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportBills(): string
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($this->user);
        $bills      = $repository->getBills();
        $header     = [
            'user_id',
            'bill_id',
            'created_at',
            'updated_at',
            'currency_code',
            'name',
            'amount_min',
            'amount_max',
            'date',
            'repeat_freq',
            'skip',
            'active',
        ];
        $records    = [];

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $records[] = [
                $this->user->id,
                $bill->id,
                $bill->created_at->toAtomString(),
                $bill->updated_at->toAtomString(),
                $bill->transactionCurrency->code,
                $bill->name,
                $bill->amount_min,
                $bill->amount_max,
                $bill->date->format('Y-m-d'),
                $bill->repeat_freq,
                $bill->skip,
                $bill->active,
            ];
        }

        // load the CSV document from a string
        $csv        = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportBudgets(): string
    {
        $header      = [
            'user_id',
            'budget_id',
            'name',
            'active',
            'order',
            'start_date',
            'end_date',
            'currency_code',
            'amount',
        ];

        $budgetRepos = app(BudgetRepositoryInterface::class);
        $budgetRepos->setUser($this->user);
        $limitRepos  = app(BudgetLimitRepositoryInterface::class);
        $budgets     = $budgetRepos->getBudgets();
        $records     = [];

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $limits = $limitRepos->getBudgetLimits($budget);

            /** @var BudgetLimit $limit */
            foreach ($limits as $limit) {
                $records[] = [
                    $this->user->id,
                    $budget->id,
                    $budget->name,
                    $budget->active,
                    $budget->order,
                    $limit->start_date->format('Y-m-d'),
                    $limit->end_date->format('Y-m-d'),
                    $limit->transactionCurrency->code,
                    $limit->amount,
                ];
            }
        }

        // load the CSV document from a string
        $csv         = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportCategories(): string
    {
        $header     = ['user_id', 'category_id', 'created_at', 'updated_at', 'name'];

        /** @var CategoryRepositoryInterface $catRepos */
        $catRepos   = app(CategoryRepositoryInterface::class);
        $catRepos->setUser($this->user);

        $records    = [];
        $categories = $catRepos->getCategories();

        /** @var Category $category */
        foreach ($categories as $category) {
            $records[] = [
                $this->user->id,
                $category->id,
                $category->created_at->toAtomString(),
                $category->updated_at->toAtomString(),
                $category->name,
            ];
        }

        // load the CSV document from a string
        $csv        = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportPiggies(): string
    {
        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos   = app(PiggyBankRepositoryInterface::class);
        $piggyRepos->setUser($this->user);

        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($this->user);

        $header       = [
            'user_id',
            'piggy_bank_id',
            'created_at',
            'updated_at',
            'account_name',
            'account_type',
            'name',
            'currency_code',
            'target_amount',
            'current_amount',
            'start_date',
            'target_date',
            'order',
            'active',
        ];
        $records      = [];
        $piggies      = $piggyRepos->getPiggyBanks();

        /** @var PiggyBank $piggy */
        foreach ($piggies as $piggy) {
            $repetition = $piggyRepos->getRepetition($piggy);
            $currency   = $accountRepos->getAccountCurrency($piggy->account);
            $records[]  = [
                $this->user->id,
                $piggy->id,
                $piggy->created_at->toAtomString(),
                $piggy->updated_at->toAtomString(),
                $piggy->account->name,
                $piggy->account->accountType->type,
                $piggy->name,
                $currency?->code,
                $piggy->target_amount,
                $repetition?->current_amount,
                $piggy->start_date?->format('Y-m-d'),
                $piggy->target_date?->format('Y-m-d'),
                $piggy->order,
                $piggy->active,
            ];
        }

        // load the CSV document from a string
        $csv          = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportRecurring(): string
    {
        /** @var RecurringRepositoryInterface $recurringRepos */
        $recurringRepos = app(RecurringRepositoryInterface::class);
        $recurringRepos->setUser($this->user);
        $header         = [
            // recurrence:
            'user_id', 'recurrence_id', 'row_contains', 'created_at', 'updated_at', 'type', 'title', 'description', 'first_date', 'repeat_until', 'latest_date', 'repetitions', 'apply_rules', 'active',

            // repetition info:
            'type', 'moment', 'skip', 'weekend',
            // transactions + meta:
            'currency_code', 'foreign_currency_code', 'source_name', 'source_type', 'destination_name', 'destination_type', 'amount', 'foreign_amount', 'category', 'budget', 'piggy_bank', 'tags',
        ];
        $records        = [];
        $recurrences    = $recurringRepos->getAll();

        /** @var Recurrence $recurrence */
        foreach ($recurrences as $recurrence) {
            // add recurrence:
            $records[] = [
                $this->user->id, $recurrence->id,
                'recurrence',
                $recurrence->created_at->toAtomString(), $recurrence->updated_at->toAtomString(), $recurrence->transactionType->type, $recurrence->title, $recurrence->description, $recurrence->first_date?->format('Y-m-d'), $recurrence->repeat_until?->format('Y-m-d'), $recurrence->latest_date?->format('Y-m-d'), $recurrence->repetitions, $recurrence->apply_rules, $recurrence->active,
            ];

            // add new row for each repetition
            /** @var RecurrenceRepetition $repetition */
            foreach ($recurrence->recurrenceRepetitions as $repetition) {
                $records[] = [
                    // recurrence
                    $this->user->id,
                    $recurrence->id,
                    'repetition',
                    null, null, null, null, null, null, null, null, null, null, null,

                    // repetition:
                    $repetition->repetition_type, $repetition->repetition_moment, $repetition->repetition_skip, $repetition->weekend,
                ];
            }

            /** @var RecurrenceTransaction $transaction */
            foreach ($recurrence->recurrenceTransactions as $transaction) {
                $categoryName = $recurringRepos->getCategoryName($transaction);
                $budgetId     = $recurringRepos->getBudget($transaction);
                $piggyBankId  = $recurringRepos->getPiggyBank($transaction);
                $tags         = $recurringRepos->getTags($transaction);

                $records[]    = [
                    // recurrence
                    $this->user->id,
                    $recurrence->id,
                    'transaction',
                    null, null, null, null, null, null, null, null, null, null, null,

                    // repetition:
                    null, null, null, null,

                    // transaction:
                    $transaction->transactionCurrency->code, $transaction->foreignCurrency?->code, $transaction->sourceAccount->name, $transaction->sourceAccount->accountType->type, $transaction->destinationAccount->name, $transaction->destinationAccount->accountType->type, $transaction->amount, $transaction->foreign_amount, $categoryName, $budgetId, $piggyBankId, implode(',', $tags),
                ];
            }
        }
        // load the CSV document from a string
        $csv            = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportRules(): string
    {
        $header    = [
            'user_id', 'rule_id', 'row_contains',
            'created_at', 'updated_at', 'group_id', 'title', 'description', 'order', 'active', 'stop_processing', 'strict',
            'trigger_type', 'trigger_value', 'trigger_order', 'trigger_active', 'trigger_stop_processing',
            'action_type', 'action_value', 'action_order', 'action_active', 'action_stop_processing'];
        $ruleRepos = app(RuleRepositoryInterface::class);
        $ruleRepos->setUser($this->user);
        $rules     = $ruleRepos->getAll();
        $records   = [];

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $entry     = [
                $this->user->id, $rule->id,
                'rule',
                $rule->created_at->toAtomString(), $rule->updated_at->toAtomString(), $rule->ruleGroup->id, $rule->ruleGroup->title, $rule->title, $rule->description, $rule->order, $rule->active, $rule->stop_processing, $rule->strict,
                null, null, null, null, null, null, null, null, null,
            ];
            $records[] = $entry;

            /** @var RuleTrigger $trigger */
            foreach ($rule->ruleTriggers as $trigger) {
                $entry     = [
                    $this->user->id,
                    $rule->id,
                    'trigger',
                    null, null, null, null, null, null, null, null, null,
                    $trigger->trigger_type, $trigger->trigger_value, $trigger->order, $trigger->active, $trigger->stop_processing,
                    null, null, null, null, null,
                ];
                $records[] = $entry;
            }

            /** @var RuleAction $action */
            foreach ($rule->ruleActions as $action) {
                $entry     = [
                    $this->user->id,
                    $rule->id,
                    'action',
                    null, null, null, null, null, null, null, null, null, null, null, null, null, null,
                    $action->action_type, $action->action_value, $action->order, $action->active, $action->stop_processing,
                ];
                $records[] = $entry;
            }
        }

        // load the CSV document from a string
        $csv       = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportTags(): string
    {
        $header   = ['user_id', 'tag_id', 'created_at', 'updated_at', 'tag', 'date', 'description', 'latitude', 'longitude', 'zoom_level'];

        $tagRepos = app(TagRepositoryInterface::class);
        $tagRepos->setUser($this->user);
        $tags     = $tagRepos->get();
        $records  = [];

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $records[] = [
                $this->user->id,
                $tag->id,
                $tag->created_at->toAtomString(),
                $tag->updated_at->toAtomString(),
                $tag->tag,
                $tag->date?->format('Y-m-d'),
                $tag->description,
                $tag->latitude,
                $tag->longitude,
                $tag->zoomLevel,
            ];
        }

        // load the CSV document from a string
        $csv      = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return null;
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws FireflyException
     */
    private function exportTransactions(): string
    {
        // TODO better place for keys?
        $header     = ['user_id', 'group_id', 'journal_id', 'created_at', 'updated_at', 'group_title', 'type', 'currency_code', 'amount', 'foreign_currency_code', 'foreign_amount', 'native_currency_code', 'native_amount', 'native_foreign_amount', 'description', 'date', 'source_name', 'source_iban', 'source_type', 'destination_name', 'destination_iban', 'destination_type', 'reconciled', 'category', 'budget', 'bill', 'tags', 'notes'];

        $metaFields = config('firefly.journal_meta_fields');
        $header     = array_merge($header, $metaFields);
        $default    = Amount::getNativeCurrency();

        $collector  = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($this->start, $this->end)->withAccountInformation()->withCategoryInformation()->withBillInformation()->withBudgetInformation()->withTagInformation()->withNotes();
        if (0 !== $this->accounts->count()) {
            $collector->setAccounts($this->accounts);
        }

        $journals   = $collector->getExtractedJournals();

        // get repository for meta data:
        $repository = app(TransactionGroupRepositoryInterface::class);
        $repository->setUser($this->user);

        $records    = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $metaData            = $repository->getMetaFields($journal['transaction_journal_id'], $metaFields);
            $amount              = Steam::bcround(Steam::negative($journal['amount']), $journal['currency_decimal_places']);
            $foreignAmount       = null === $journal['foreign_amount'] ? null : Steam::bcround(Steam::negative($journal['foreign_amount']), $journal['foreign_currency_decimal_places']);
            $nativeAmount        = null === $journal['native_amount'] ? null : Steam::bcround(Steam::negative($journal['native_amount']), $default->decimal_places);
            $nativeForeignAmount = null === $journal['native_foreign_amount'] ? null : Steam::bcround(Steam::negative($journal['native_foreign_amount']), $default->decimal_places);

            if (TransactionTypeEnum::WITHDRAWAL->value !== $journal['transaction_type_type']) {
                $amount              = Steam::bcround(Steam::positive($journal['amount']), $journal['currency_decimal_places']);
                $foreignAmount       = null === $journal['foreign_amount'] ? null : Steam::bcround(Steam::positive($journal['foreign_amount']), $journal['foreign_currency_decimal_places']);
                $nativeAmount        = null === $journal['native_amount'] ? null : Steam::bcround(Steam::positive($journal['native_amount']), $default->decimal_places);
                $nativeForeignAmount = null === $journal['native_foreign_amount'] ? null : Steam::bcround(Steam::positive($journal['native_foreign_amount']), $default->decimal_places);
            }

            // opening balance depends on source account type.
            if (TransactionTypeEnum::OPENING_BALANCE->value === $journal['transaction_type_type'] && AccountTypeEnum::ASSET->value === $journal['source_account_type']) {
                $amount              = Steam::bcround(Steam::negative($journal['amount']), $journal['currency_decimal_places']);
                $foreignAmount       = null === $journal['foreign_amount'] ? null : Steam::bcround(Steam::negative($journal['foreign_amount']), $journal['foreign_currency_decimal_places']);
                $nativeAmount        = null === $journal['native_amount'] ? null : Steam::bcround(Steam::negative($journal['native_amount']), $default->decimal_places);
                $nativeForeignAmount = null === $journal['native_foreign_amount'] ? null : Steam::bcround(Steam::negative($journal['native_foreign_amount']), $default->decimal_places);
            }

            $records[]           = [
                $journal['user_id'], $journal['transaction_group_id'], $journal['transaction_journal_id'], $journal['created_at']->toAtomString(), $journal['updated_at']->toAtomString(), $journal['transaction_group_title'], $journal['transaction_type_type'],
                // amounts and currencies
                $journal['currency_code'], $amount, $journal['foreign_currency_code'], $foreignAmount, $default->code, $nativeAmount, $nativeForeignAmount,

                // more fields
                $journal['description'], $journal['date']->toAtomString(), $journal['source_account_name'], $journal['source_account_iban'], $journal['source_account_type'], $journal['destination_account_name'], $journal['destination_account_iban'], $journal['destination_account_type'], $journal['reconciled'], $journal['category_name'], $journal['budget_name'], $journal['bill_name'],
                $this->mergeTags($journal['tags']),
                $this->clearStringKeepNewlines($journal['notes']),

                // sepa
                $metaData['sepa_cc'], $metaData['sepa_ct_op'], $metaData['sepa_ct_id'], $metaData['sepa_db'], $metaData['sepa_country'], $metaData['sepa_ep'], $metaData['sepa_ci'], $metaData['sepa_batch_id'], $metaData['external_url'],

                // dates
                $metaData['interest_date'], $metaData['book_date'], $metaData['process_date'], $metaData['due_date'], $metaData['payment_date'], $metaData['invoice_date'],

                // others
                $metaData['recurrence_id'], $metaData['internal_reference'], $metaData['bunq_payment_id'], $metaData['import_hash'], $metaData['import_hash_v2'], $metaData['external_id'], $metaData['original_source'],

                // recurring transactions
                $metaData['recurrence_total'], $metaData['recurrence_count'],
            ];
        }

        // load the CSV document from a string
        $csv        = Writer::createFromString();

        // insert the header
        try {
            $csv->insertOne($header);
        } catch (CannotInsertRecord $e) {
            throw new FireflyException(sprintf(self::ADD_RECORD_ERR, $e->getMessage()), 0, $e);
        }

        // insert all the records
        $csv->insertAll($records);

        try {
            $string = $csv->toString();
        } catch (Exception $e) { // intentional generic exception
            app('log')->error($e->getMessage());

            throw new FireflyException(sprintf(self::EXPORT_ERR, $e->getMessage()), 0, $e);
        }

        return $string;
    }

    public function setAccounts(Collection $accounts): void
    {
        $this->accounts = $accounts;
    }

    private function mergeTags(array $tags): string
    {
        if (0 === count($tags)) {
            return '';
        }
        $smol = [];
        foreach ($tags as $tag) {
            $smol[] = $tag['name'];
        }

        return implode(',', $smol);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function has(mixed $key): mixed
    {
        return null;
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setExportAccounts(bool $exportAccounts): void
    {
        $this->exportAccounts = $exportAccounts;
    }

    public function setExportBills(bool $exportBills): void
    {
        $this->exportBills = $exportBills;
    }

    public function setExportBudgets(bool $exportBudgets): void
    {
        $this->exportBudgets = $exportBudgets;
    }

    public function setExportCategories(bool $exportCategories): void
    {
        $this->exportCategories = $exportCategories;
    }

    public function setExportPiggies(bool $exportPiggies): void
    {
        $this->exportPiggies = $exportPiggies;
    }

    public function setExportRecurring(bool $exportRecurring): void
    {
        $this->exportRecurring = $exportRecurring;
    }

    public function setExportRules(bool $exportRules): void
    {
        $this->exportRules = $exportRules;
    }

    public function setExportTags(bool $exportTags): void
    {
        $this->exportTags = $exportTags;
    }

    public function setExportTransactions(bool $exportTransactions): void
    {
        $this->exportTransactions = $exportTransactions;
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }
}
