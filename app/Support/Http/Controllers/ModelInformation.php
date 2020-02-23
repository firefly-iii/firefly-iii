<?php
/**
 * ModelInformation.php
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

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Throwable;

/**
 * Trait ModelInformation
 *
 */
trait ModelInformation
{
    /**
     * Get actions based on a bill.
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function getActionsForBill(Bill $bill): array // get info and augument
    {
        try {
            $result = view(
                'rules.partials.action',
                [
                    'oldAction'  => 'link_to_bill',
                    'oldValue'   => $bill->name,
                    'oldChecked' => false,
                    'count'      => 1,
                ]
            )->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Throwable was thrown in getActionsForBill(): %s', $e->getMessage()));
            Log::error($e->getTraceAsString());
            $result = 'Could not render view. See log files.';
        }

        // @codeCoverageIgnoreEnd

        return [$result];
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    protected function getLiabilityTypes(): array
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        // types of liability:
        $debt     = $repository->getAccountTypeByType(AccountType::DEBT);
        $loan     = $repository->getAccountTypeByType(AccountType::LOAN);
        $mortgage = $repository->getAccountTypeByType(AccountType::MORTGAGE);
        /** @noinspection NullPointerExceptionInspection */
        $liabilityTypes = [
            $debt->id     => (string)trans(sprintf('firefly.account_type_%s', AccountType::DEBT)),
            $loan->id     => (string)trans(sprintf('firefly.account_type_%s', AccountType::LOAN)),
            $mortgage->id => (string)trans(sprintf('firefly.account_type_%s', AccountType::MORTGAGE)),
        ];
        asort($liabilityTypes);

        return $liabilityTypes;
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    protected function getRoles(): array
    {
        $roles = [];
        foreach (config('firefly.accountRoles') as $role) {
            $roles[$role] = (string)trans(sprintf('firefly.account_role_%s', $role));
        }

        return $roles;
    }

    /**
     * Create fake triggers to match the bill's properties
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function getTriggersForBill(Bill $bill): array // get info and augument
    {
        $result   = [];
        $triggers = ['currency_is', 'amount_more', 'amount_less', 'description_contains'];
        $values   = [
            $bill->transactionCurrency()->first()->name,
            round((float)$bill->amount_min, 12),
            round((float)$bill->amount_max, 12),
            $bill->name,
        ];
        foreach ($triggers as $index => $trigger) {
            try {
                $string = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => $trigger,
                        'oldValue'   => $values[$index],
                        'oldChecked' => false,
                        'count'      => $index + 1,
                    ]
                )->render();
                // @codeCoverageIgnoreStart
            } catch (Throwable $e) {

                Log::debug(sprintf('Throwable was thrown in getTriggersForBill(): %s', $e->getMessage()));
                Log::debug($e->getTraceAsString());
                $string = '';
                // @codeCoverageIgnoreEnd
            }
            if ('' !== $string) {
                $result[] = $string;
            }
        }

        return $result;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     */
    private function getTriggersForJournal(TransactionJournal $journal): array
    {
        $result   = [];
        $triggers = [];
        $values   = [];
        $index    = 0;
        // amount, description, category, budget, tags, source, destination, notes, currency type
        //,type
        /** @var Transaction $source */
        $source = $journal->transactions()->where('amount', '<', 0)->first();
        /** @var Transaction $destination */
        $destination = $journal->transactions()->where('amount', '>', 0)->first();
        if (null === $destination || null === $source) {
            return $result;
        }
        // type
        $triggers[$index] = 'transaction_type';
        $values[$index]   = $journal->transactionType->type;
        $index++;

        // currency
        $triggers[$index] = 'currency_is';
        $values[$index]   = sprintf('%s (%s)', $journal->transactionCurrency->name, $journal->transactionCurrency->code);
        $index++;

        // amount_exactly:
        $triggers[$index] = 'amount_exactly';
        $values[$index]   = $destination->amount;
        $index++;

        // description_is:
        $triggers[$index] = 'description_is';
        $values[$index]   = $journal->description;
        $index++;

        // from_account_is
        $triggers[$index] = 'from_account_is';
        $values[$index]   = $source->account->name;
        $index++;

        // to_account_is
        $triggers[$index] = 'to_account_is';
        $values[$index]   = $destination->account->name;
        $index++;

        // category (if)
        $category = $journal->categories()->first();
        if (null !== $category) {
            $triggers[$index] = 'category_is';
            $values[$index]   = $category->name;
            $index++;
        }
        // budget (if)
        $budget = $journal->budgets()->first();
        if (null !== $budget) {
            $triggers[$index] = 'budget_is';
            $values[$index]   = $budget->name;
            $index++;
        }
        // tags (if)
        $tags = $journal->tags()->get();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $triggers[$index] = 'tag_is';
            $values[$index]   = $tag->tag;
            $index++;
        }
        // notes (if)
        $notes = $journal->notes()->first();
        if (null !== $notes) {
            $triggers[$index] = 'notes_are';
            $values[$index]   = $notes->text;
            $index++;
        }

        foreach ($triggers as $index => $trigger) {
            try {
                $string = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => $trigger,
                        'oldValue'   => $values[$index],
                        'oldChecked' => false,
                        'count'      => $index + 1,
                    ]
                )->render();
                // @codeCoverageIgnoreStart
            } catch (Throwable $e) {

                Log::debug(sprintf('Throwable was thrown in getTriggersForJournal(): %s', $e->getMessage()));
                Log::debug($e->getTraceAsString());
                $string = '';
                // @codeCoverageIgnoreEnd
            }
            if ('' !== $string) {
                $result[] = $string;
            }
        }

        return $result;
    }
}
