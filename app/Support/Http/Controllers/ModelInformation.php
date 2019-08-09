<?php
/**
 * ModelInformation.php
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

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
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
}
