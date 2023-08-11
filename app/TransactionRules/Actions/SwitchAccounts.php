<?php
/**
 * ConvertToWithdrawal.php
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

use DB;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Facades\Log;

/**
 *
 * Class SwitchAccounts
 */
class SwitchAccounts implements ActionInterface
{
    private RuleAction $action;

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
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        // make object from array (so the data is fresh).
        /** @var TransactionJournal|null $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            Log::error(sprintf('Cannot find journal #%d, cannot switch accounts.', $journal['transaction_journal_id']));
            return false;
        }
        $groupCount = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            Log::error(sprintf('Group #%d has more than one transaction in it, cannot switch accounts.', $journal['transaction_group_id']));
            return false;
        }

        $type = $object->transactionType->type;
        if (TransactionType::TRANSFER !== $type) {
            Log::error(sprintf('Journal #%d is NOT a transfer (rule #%d), cannot switch accounts.', $journal['transaction_journal_id'], $this->action->rule_id));

            return false;
        }

        /** @var Transaction $sourceTransaction */
        $sourceTransaction = $object->transactions()->where('amount', '<', 0)->first();
        /** @var Transaction $destTransaction */
        $destTransaction = $object->transactions()->where('amount', '>', 0)->first();
        if (null === $sourceTransaction || null === $destTransaction) {
            Log::error(sprintf('Journal #%d has no source or destination transaction (rule #%d), cannot switch accounts.', $journal['transaction_journal_id'], $this->action->rule_id));

            return false;
        }
        $sourceAccountId               = (int)$sourceTransaction->account_id;
        $destinationAccountId          = $destTransaction->account_id;
        $sourceTransaction->account_id = $destinationAccountId;
        $destTransaction->account_id   = $sourceAccountId;
        $sourceTransaction->save();
        $destTransaction->save();

        event(new TriggeredAuditLog($this->action->rule, $object, 'switch_accounts', $sourceAccountId, $destinationAccountId));

        return true;
    }
}
