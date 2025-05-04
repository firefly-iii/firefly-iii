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

use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;

/**
 * Class DeleteTransaction.
 */
class DeleteTransaction implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action)
    {
    }

    public function actOnArray(array $journal): bool
    {
        $count  = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();

        // destroy entire group.
        if (1 === $count) {
            app('log')->debug(
                sprintf(
                    'RuleAction DeleteTransaction DELETED the entire transaction group of journal #%d ("%s").',
                    $journal['transaction_journal_id'],
                    $journal['description']
                )
            );

            /** @var TransactionGroup $group */
            $group   = TransactionGroup::find($journal['transaction_group_id']);
            $service = app(TransactionGroupDestroyService::class);
            $service->destroy($group);

            event(new TriggeredAuditLog($this->action->rule, $group, 'delete_group', null, null));

            return true;
        }
        app('log')->debug(
            sprintf('RuleAction DeleteTransaction DELETED transaction journal #%d ("%s").', $journal['transaction_journal_id'], $journal['description'])
        );

        // trigger delete factory:
        /** @var null|TransactionJournal $object */
        $object = TransactionJournal::find($journal['transaction_journal_id']);
        if (null !== $object) {
            /** @var JournalDestroyService $service */
            $service = app(JournalDestroyService::class);
            $service->destroy($object);
            event(new TriggeredAuditLog($this->action->rule, $object, 'delete_journal', null, null));
        }

        return true;
    }
}
