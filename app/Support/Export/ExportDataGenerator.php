<?php
/**
 * ExportDataGenerator.php
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

namespace FireflyIII\Support\Export;

use Carbon\Carbon;
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
use FireflyIII\User;
use League\Csv\Writer;

/**
 * Class ExportDataGenerator
 */
class ExportDataGenerator
{
    /** @var Carbon */
    private $end;
    /** @var bool */
    private $exportTransactions;
    /** @var Carbon */
    private $start;
    /** @var bool */
    private $exportAccounts;
    /** @var bool */
    private $exportBudgets;
    /** @var bool */
    private $exportCategories;
    /** @var bool */
    private $exportTags;
    /** @var bool */
    private $exportRecurring;
    /** @var bool */
    private $exportRules;
    /** @var bool */
    private $exportBills;
    /** @var bool */
    private $exportPiggies;

    /** @var User */
    private $user;

    public function __construct()
    {
        $this->start = new Carbon;
        $this->start->subYear();
        $this->end                = new Carbon;
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
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array
     * @throws \League\Csv\CannotInsertRecord
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
     * @param bool $exportAccounts
     */
    public function setExportAccounts(bool $exportAccounts): void
    {
        $this->exportAccounts = $exportAccounts;
    }

    /**
     * @param bool $exportBudgets
     */
    public function setExportBudgets(bool $exportBudgets): void
    {
        $this->exportBudgets = $exportBudgets;
    }

    /**
     * @param bool $exportCategories
     */
    public function setExportCategories(bool $exportCategories): void
    {
        $this->exportCategories = $exportCategories;
    }

    /**
     * @param bool $exportTags
     */
    public function setExportTags(bool $exportTags): void
    {
        $this->exportTags = $exportTags;
    }

    /**
     * @param bool $exportRecurring
     */
    public function setExportRecurring(bool $exportRecurring): void
    {
        $this->exportRecurring = $exportRecurring;
    }

    /**
     * @param bool $exportRules
     */
    public function setExportRules(bool $exportRules): void
    {
        $this->exportRules = $exportRules;
    }

    /**
     * @param bool $exportBills
     */
    public function setExportBills(bool $exportBills): void
    {
        $this->exportBills = $exportBills;
    }

    /**
     * @param bool $exportPiggies
     */
    public function setExportPiggies(bool $exportPiggies): void
    {
        $this->exportPiggies = $exportPiggies;
    }

    /**
     * @param Carbon $end
     */
    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    /**
     * @param bool $exportTransactions
     */
    public function setExportTransactions(bool $exportTransactions): void
    {
        $this->exportTransactions = $exportTransactions;
    }

    /**
     * @param Carbon $start
     */
    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    /**
     * @return string
     */
    private function exportRules(): string
    {
        $header    = ['user_id', 'rule_id', 'row_contains', 'created_at', 'updated_at', 'group_id', 'group_name', 'title', 'description', 'order', 'active',
                      'stop_processing', 'strict', 'trigger_type', 'trigger_value', 'trigger_order', 'trigger_active', 'trigger_stop_processing', 'action_type',
                      'action_value', 'action_order', 'action_active', 'action_stop_processing',];
        $ruleRepos = app(RuleRepositoryInterface::class);
        $ruleRepos->setUser($this->user);
        $rules   = $ruleRepos->getAll();
        $records = [];
        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $records[] = [
                $this->user->id, $rule->id, 'rule',
                $rule->created_at->toAtomString(), $rule->updated_at->toAtomString(),
                $rule->ruleGroup->id, $rule->ruleGroup->name,
                $rule->title, $rule->description, $rule->order, $rule->active, $rule->stop_processing, $rule->strict,
            ];
            /** @var RuleTrigger $trigger */
            foreach ($rule->ruleTriggers as $trigger) {
                $records[] = [
                    $this->user->id, $rule->id, 'trigger',
                    null, null,
                    null, null,
                    null, null, null, null, null, null,
                    $trigger->trigger_type, $trigger->trigger_value, $trigger->order, $trigger->active, $trigger->stop_processing,
                ];
            }

            /** @var RuleAction $action */
            foreach ($rule->ruleActions as $action) {
                $records[] = [
                    $this->user->id, $rule->id, 'action',
                    null, null,
                    null, null,
                    null, null, null, null, null, null,
                    null, null, null, null, null,
                    $action->action_type, $action->action_value, $action->order, $action->active, $action->stop_processing,
                ];
            }
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportAccounts(): string
    {
        $header = ['user_id', 'account_id', 'created_at', 'updated_at', 'type', 'name', 'virtual_balance', 'iban', 'number', 'active', 'currency_code', 'role',
                   'cc_type', 'cc_payment_date', 'in_net_worth', 'interest', 'interest_period',];
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);
        $accounts = $repository->getAccountsByType([]);
        $records  = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
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
                $currency ? $currency->code : null,
                $repository->getMetaValue($account, 'account_role'),
                $repository->getMetaValue($account, 'cc_type'),
                $repository->getMetaValue($account, 'cc_monthly_payment_date'),
                $repository->getMetaValue($account, 'include_net_worth'),
                $repository->getMetaValue($account, 'interest'),
                $repository->getMetaValue($account, 'interest_period'),
            ];
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportBills(): string
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($this->user);
        $bills   = $repository->getBills();
        $header  = ['user_id', 'bill_id', 'created_at', 'updated_at', 'currency_code', 'name', 'amount_min', 'amount_max', 'date', 'repeat_freq', 'skip',
                    'active',];
        $records = [];

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

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     * @throws \League\Csv\CannotInsertRecord
     */
    private function exportBudgets(): string
    {
        $header = [
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
        $limitRepos = app(BudgetLimitRepositoryInterface::class);
        $budgets    = $budgetRepos->getBudgets();
        $records    = [];
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

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string

    }

    /**
     * @return string
     */
    private function exportCategories(): string
    {
        $header = ['user_id', 'category_id', 'created_at', 'updated_at', 'name'];

        /** @var CategoryRepositoryInterface $catRepos */
        $catRepos = app(CategoryRepositoryInterface::class);
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

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportPiggies(): string
    {
        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos = app(PiggyBankRepositoryInterface::class);
        $piggyRepos->setUser($this->user);

        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($this->user);

        $header  = ['user_id', 'piggy_bank_id', 'created_at', 'updated_at', 'account_name', 'account_type', 'name',
                    'currency_code', 'target_amount', 'current_amount', 'start_date', 'target_date', 'order',
                    'active'];
        $records = [];
        $piggies = $piggyRepos->getPiggyBanks();

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
                $currency ? $currency->code : null,
                $piggy->targetamount,
                $repetition ? $repetition->currentamount : null,
                $piggy->startdate->format('Y-m-d'),
                $piggy->targetdate ? $piggy->targetdate->format('Y-m-d') : null,
                $piggy->order,
                $piggy->active,
            ];
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportRecurring(): string
    {
        /** @var RecurringRepositoryInterface $recurringRepos */
        $recurringRepos = app(RecurringRepositoryInterface::class);
        $recurringRepos->setUser($this->user);
        $header      = [
            // recurrence:
            'user_id', 'recurrence_id', 'row_contains', 'created_at', 'updated_at', 'type', 'title', 'description', 'first_date', 'repeat_until',
            'latest_date', 'repetitions', 'apply_rules', 'active',

            // repetition info:
            'type', 'moment', 'skip', 'weekend',
            // transactions + meta:
            'currency_code', 'foreign_currency_code', 'source_name', 'source_type', 'destination_name', 'destination_type', 'amount', 'foreign_amount',
            'category', 'budget', 'piggy_bank', 'tags',
        ];
        $records     = [];
        $recurrences = $recurringRepos->getAll();
        /** @var Recurrence $recurrence */
        foreach ($recurrences as $recurrence) {
            // add recurrence:
            $records[] = [
                $this->user->id,
                $recurrence->id,
                'recurrence',
                $recurrence->created_at->toAtomString(),
                $recurrence->updated_at->toAtomString(),
                $recurrence->transactionType->type,
                $recurrence->title,
                $recurrence->description,
                $recurrence->first_date ? $recurrence->first_date->format('Y-m-d') : null,
                $recurrence->repeat_until ? $recurrence->repeat_until->format('Y-m-d') : null,
                $recurrence->latest_date ? $recurrence->repeat_until->format('Y-m-d') : null,
                $recurrence->repetitions,
                $recurrence->apply_rules,
                $recurrence->active,
            ];
            // add new row for each repetition
            /** @var RecurrenceRepetition $repetition */
            foreach ($recurrence->recurrenceRepetitions as $repetition) {
                $records[] = [
                    // recurrence
                    $this->user->id,
                    $recurrence->id, 'repetition', null, null, null, null, null, null, null, null, null, null, null,

                    // repetition:
                    $repetition->repetition_type, $repetition->repetition_moment, $repetition->repetition_skip, $repetition->weekend,
                ];
            }
            /** @var RecurrenceTransaction $transaction */
            foreach ($recurrence->recurrenceTransactions as $transaction) {
                $categoryName = $recurringRepos->getCategory($transaction);
                $budgetId     = $recurringRepos->getBudget($transaction);
                $piggyBankId  = $recurringRepos->getPiggyBank($transaction);
                $tags         = $recurringRepos->getTags($transaction);

                $records[] = [
                    // recurrence
                    $this->user->id,
                    $recurrence->id, 'transaction', null, null, null, null, null, null, null, null, null, null, null,

                    // repetition:
                    null, null, null, null,

                    // transaction:
                    $transaction->transactionCurrency->code, $transaction->foreignCurrency ? $transaction->foreignCurrency->code : null,
                    $transaction->sourceAccount->name, $transaction->sourceAccount->accountType->type, $transaction->destinationAccount->name,
                    $transaction->destinationAccount->accountType->type, $transaction->amount, $transaction->foreign_amount,
                    $categoryName, $budgetId, $piggyBankId, implode(',', $tags),
                ];
            }
        }
        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportTags(): string
    {
        $header = ['user_id', 'tag_id', 'created_at', 'updated_at', 'tag', 'date', 'description', 'latitude', 'longitude', 'zoom_level'];

        $tagRepos = app(TagRepositoryInterface::class);
        $tagRepos->setUser($this->user);
        $tags    = $tagRepos->get();
        $records = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $records[] = [
                $this->user->id,
                $tag->id,
                $tag->created_at->toAtomString(),
                $tag->updated_at->toAtomString(),
                $tag->tag,
                $tag->date ? $tag->date->format('Y-m-d') : null,
                $tag->description,
                $tag->latitude,
                $tag->longitude,
                $tag->zoomLevel,
            ];
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

    /**
     * @return string
     */
    private function exportTransactions(): string
    {
        // TODO better place for keys?
        $header    = ['user_id', 'group_id', 'journal_id', 'created_at', 'updated_at', 'group_title', 'type', 'amount', 'foreign_amount', 'currency_code',
                      'foreign_currency_code', 'description', 'date', 'source_name', 'source_iban', 'source_type', 'destination_name', 'destination_iban',
                      'destination_type', 'reconciled', 'category', 'budget', 'bill', 'tags',];
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($this->start, $this->end)->withAccountInformation()->withCategoryInformation()->withBillInformation()
                  ->withBudgetInformation();
        $journals = $collector->getExtractedJournals();

        $records = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            $records[] = [
                $journal['user_id'],
                $journal['transaction_group_id'],
                $journal['transaction_journal_id'],
                $journal['created_at']->toAtomString(),
                $journal['updated_at']->toAtomString(),
                $journal['transaction_group_title'],
                $journal['transaction_type_type'],
                $journal['amount'],
                $journal['foreign_amount'],
                $journal['currency_code'],
                $journal['foreign_currency_code'],
                $journal['description'],
                $journal['date']->toAtomString(),
                $journal['source_account_name'],
                $journal['source_account_iban'],
                $journal['source_account_type'],
                $journal['destination_account_name'],
                $journal['destination_account_iban'],
                $journal['destination_account_type'],
                $journal['reconciled'],
                $journal['category_name'],
                $journal['budget_name'],
                $journal['bill_name'],
                implode(',', $journal['tags']),
            ];
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

}
