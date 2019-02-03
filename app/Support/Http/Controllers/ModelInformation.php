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
     * Get the destination account. Is complex.
     *
     * @param TransactionJournal $journal
     * @param TransactionType    $destinationType
     * @param array              $data
     *
     * @return Account
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getDestinationAccount(TransactionJournal $journal, TransactionType $destinationType, array $data
    ): Account // helper for conversion. Get info from obj.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos       = app(JournalRepositoryInterface::class);
        $sourceAccount      = $journalRepos->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $journalRepos->getJournalDestinationAccounts($journal)->first();
        $sourceType         = $journal->transactionType;
        $joined             = $sourceType->type . '-' . $destinationType->type;
        switch ($joined) {
            default:
                throw new FireflyException('Cannot handle ' . $joined); // @codeCoverageIgnore
            case TransactionType::WITHDRAWAL . '-' . TransactionType::DEPOSIT:
                // one
                $destination = $sourceAccount;
                break;
            case TransactionType::WITHDRAWAL . '-' . TransactionType::TRANSFER:
                // two
                $destination = $accountRepository->findNull((int)$data['destination_account_asset']);
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::WITHDRAWAL:
            case TransactionType::TRANSFER . '-' . TransactionType::WITHDRAWAL:
                // three and five
                if ('' === $data['destination_account_expense'] || null === $data['destination_account_expense']) {
                    // destination is a cash account.
                    return $accountRepository->getCashAccount();
                }
                $data        = [
                    'name'            => $data['destination_account_expense'],
                    'accountType'     => 'expense',
                    'account_type_id' => null,
                    'virtualBalance'  => 0,
                    'active'          => true,
                    'iban'            => null,
                ];
                $destination = $accountRepository->store($data);
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::TRANSFER:
            case TransactionType::TRANSFER . '-' . TransactionType::DEPOSIT:
                // four and six
                $destination = $destinationAccount;
                break;
        }

        return $destination;
    }

    /**
     * Get the source account.
     *
     * @param TransactionJournal $journal
     * @param TransactionType    $destinationType
     * @param array              $data
     *
     * @return Account
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getSourceAccount(TransactionJournal $journal, TransactionType $destinationType, array $data
    ): Account // helper for conversion. Get info from obj.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos       = app(JournalRepositoryInterface::class);
        $sourceAccount      = $journalRepos->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $journalRepos->getJournalDestinationAccounts($journal)->first();
        $sourceType         = $journal->transactionType;
        $joined             = $sourceType->type . '-' . $destinationType->type;
        switch ($joined) {
            default:
                throw new FireflyException('Cannot handle ' . $joined); // @codeCoverageIgnore
            case TransactionType::WITHDRAWAL . '-' . TransactionType::DEPOSIT:
            case TransactionType::TRANSFER . '-' . TransactionType::DEPOSIT:

                if ('' === $data['source_account_revenue'] || null === $data['source_account_revenue']) {
                    // destination is a cash account.
                    return $accountRepository->getCashAccount();
                }

                $data   = [
                    'name'            => $data['source_account_revenue'],
                    'accountType'     => 'revenue',
                    'virtualBalance'  => 0,
                    'active'          => true,
                    'account_type_id' => null,
                    'iban'            => null,
                ];
                $source = $accountRepository->store($data);
                break;
            case TransactionType::WITHDRAWAL . '-' . TransactionType::TRANSFER:
            case TransactionType::TRANSFER . '-' . TransactionType::WITHDRAWAL:
                $source = $sourceAccount;
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::WITHDRAWAL:
                $source = $destinationAccount;
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::TRANSFER:
                $source = $accountRepository->findNull((int)$data['source_account_asset']);
                break;
        }

        return $source;
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
     * Is transaction opening balance?
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function isOpeningBalance(TransactionJournal $journal): bool
    {
        return TransactionType::OPENING_BALANCE === $journal->transactionType->type;
    }

    /**
     * Checks if journal is split.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function isSplitJournal(TransactionJournal $journal): bool // validate objects
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $repository->setUser($journal->user);
        $count = $repository->countTransactions($journal);

        return $count > 2;
    }

}
