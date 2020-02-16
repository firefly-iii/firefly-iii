<?php
/**
 * ConvertToTransfer.php
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

namespace FireflyIII\TransactionRules\Actions;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 *
 * Class ConvertToTransfer
 */
class ConvertToTransfer implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * Execute the action.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $type = $journal->transactionType->type;
        if (TransactionType::TRANSFER === $type) {
            // @codeCoverageIgnoreStart
            Log::error(sprintf('Journal #%d is already a transfer so cannot be converted (rule "%s").', $journal->id, $this->action->rule->title));

            return false;
            // @codeCoverageIgnoreEnd
        }
        // find the asset account in the action value.
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($journal->user);
        $asset = $repository->findByName(
            $this->action->action_value, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]
        );
        if (null === $asset) {
            // @codeCoverageIgnoreStart
            Log::error(
                sprintf(
                    'Journal #%d cannot be converted because no asset with name "%s" exists (rule "%s").', $journal->id, $this->action->action_value,
                    $this->action->rule->title
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }

        $destTransactions   = $journal->transactions()->where('amount', '>', 0)->get();
        $sourceTransactions = $journal->transactions()->where('amount', '<', 0)->get();

        // break if count is zero:
        if (1 !== $sourceTransactions->count()) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has %d source transactions. ConvertToTransfer failed. (rule "%s").',
                    [$journal->id, $sourceTransactions->count(), $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }
        if (0 === $destTransactions->count()) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has %d dest transactions. ConvertToTransfer failed. (rule "%s").',
                    [$journal->id, $destTransactions->count(), $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }


        if (TransactionType::WITHDRAWAL === $type) {
            Log::debug('Going to transform a withdrawal to a transfer.');

            return $this->convertWithdrawal($journal, $asset);
        }
        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a transfer.');

            return $this->convertDeposit($journal, $asset);
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * A deposit is from Revenue to Asset.
     * We replace the Revenue with another asset.
     *
     * @param TransactionJournal $journal
     * @param Account            $assetAccount
     *
     * @return bool
     */
    private function convertDeposit(TransactionJournal $journal, Account $assetAccount): bool
    {
        /** @var Account $destinationAsset */
        $destinationAsset = $journal->transactions()->where('amount', '>', 0)->first()->account;
        if ($destinationAsset->id === $assetAccount->id) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a destination asset. ConvertToTransfer failed. (rule "%s").',
                    [$journal->id, $assetAccount->name, $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }
        // update source transactions
        $journal->transactions()->where('amount', '<', 0)
                ->update(['account_id' => $assetAccount->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();
        Log::debug('Converted deposit to transfer.');

        return true;
    }

    /**
     * A withdrawal is from Asset to Expense.
     * We replace the Expense with another asset.
     *
     * @param TransactionJournal $journal
     * @param Account            $assetAccount
     *
     * @return bool
     */
    private function convertWithdrawal(TransactionJournal $journal, Account $assetAccount): bool
    {
        /** @var Account $sourceAsset */
        $sourceAsset = $journal->transactions()->where('amount', '<', 0)->first()->account;
        if ($sourceAsset->id === $assetAccount->id) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a source asset. ConvertToTransfer failed. (rule "%s").',
                    [$journal->id, $assetAccount->name, $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }
        // update destination transactions
        $journal->transactions()->where('amount', '>', 0)
                ->update(['account_id' => $assetAccount->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();
        Log::debug('Converted withdrawal to transfer.');

        return true;
    }
}
