<?php
/**
 * DeleteTransaction.php
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

use Exception;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use Log;

/**
 * Class DeleteTransaction.
 */
class DeleteTransaction implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
    }

    /**
     * Will delete transaction journal. Also the group if no other journals are in the group.
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws Exception
     * @deprecated
     * @codeCoverageIgnore
     */
    public function act(TransactionJournal $journal): bool
    {

        $count = $journal->transactionGroup->transactionJournals()->count();

        // destroy entire group.
        if (1 === $count) {
            Log::debug(
                sprintf(
                    'RuleAction DeleteTransaction DELETED the entire transaction group of journal #%d ("%s").',
                    $journal->id, $journal->description
                )
            );
            $service = app(TransactionGroupDestroyService::class);
            $service->destroy($journal->transactionGroup);

            return true;
        }
        Log::debug(sprintf('RuleAction DeleteTransaction DELETED transaction journal #%d ("%s").', $journal->id, $journal->description));

        // trigger delete factory:
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        $service->destroy($journal);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        $count = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();

        // destroy entire group.
        if (1 === $count) {
            Log::debug(
                sprintf(
                    'RuleAction DeleteTransaction DELETED the entire transaction group of journal #%d ("%s").',
                    $journal['transaction_journal_id'], $journal['description']
                )
            );
            $group = TransactionGroup::find($journal['transaction_group_id']);
            $service = app(TransactionGroupDestroyService::class);
            $service->destroy($group);

            return true;
        }
        Log::debug(sprintf('RuleAction DeleteTransaction DELETED transaction journal #%d ("%s").', $journal['transaction_journal_id'], $journal['description']));

        // trigger delete factory:
        $journal = TransactionJournal::find($journal['transaction_group_id']);
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        $service->destroy($journal);

        return true;
    }
}
