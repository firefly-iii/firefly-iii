<?php

/*
 * SetAmount.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Traits\RefreshNotesTrait;

class SetAmount implements ActionInterface
{
    use RefreshNotesTrait;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(private RuleAction $action)
    {
    }

    public function actOnArray(array $journal): bool
    {
        $this->refreshNotes($journal);

        // not on slpit transactions
        $groupCount = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            app('log')->error(sprintf('Group #%d has more than one transaction in it, cannot convert to transfer.', $journal['transaction_group_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.split_group')));

            return false;
        }

        $value      = $this->action->getValue($journal);

        if (!is_numeric($value) || 0 === bccomp($value, '0')) {
            app('log')->debug(sprintf('RuleAction SetAmount, amount "%s" is not a number or is zero, will not continue.', $value));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_invalid_amount', ['amount' => $value])));

            return false;
        }

        /** @var TransactionJournal $object */
        $object     = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);

        $positive   = app('steam')->positive($value);
        $negative   = app('steam')->negative($value);

        $this->updatePositive($object, $positive);
        $this->updateNegative($object, $negative);
        $object->transactionGroup->touch();

        // event for audit log entry
        event(new TriggeredAuditLog(
            $this->action->rule,
            $object,
            'update_amount',
            [
                'currency_symbol' => $object->transactionCurrency->symbol,
                'decimal_places'  => $object->transactionCurrency->decimal_places,
                'amount'          => $journal['amount'],
            ],
            [
                'currency_symbol' => $object->transactionCurrency->symbol,
                'decimal_places'  => $object->transactionCurrency->decimal_places,
                'amount'          => $value,
            ]
        ));

        return true;
    }

    private function updatePositive(TransactionJournal $object, string $amount): void
    {
        /** @var null|Transaction $transaction */
        $transaction = $object->transactions()->where('amount', '>', 0)->first();
        if (null === $transaction) {
            return;
        }
        $this->updateAmount($transaction, $amount);
    }

    private function updateAmount(Transaction $transaction, string $amount): void
    {
        $transaction->amount = $amount;
        $transaction->save();
        $transaction->transactionJournal->touch();
    }

    private function updateNegative(TransactionJournal $object, string $amount): void
    {
        /** @var null|Transaction $transaction */
        $transaction = $object->transactions()->where('amount', '<', 0)->first();
        if (null === $transaction) {
            return;
        }
        $this->updateAmount($transaction, $amount);
    }
}
