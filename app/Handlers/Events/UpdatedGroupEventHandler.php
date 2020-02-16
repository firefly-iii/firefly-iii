<?php
/**
 * UpdatedGroupEventHandler.php
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Engine\RuleEngine;

/**
 * Class UpdatedGroupEventHandler
 */
class UpdatedGroupEventHandler
{
    /**
     * This method will check all the rules when a journal is updated.
     *
     * @param UpdatedTransactionGroup $updatedJournalEvent
     */
    public function processRules(UpdatedTransactionGroup $updatedJournalEvent): void
    {
        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser($updatedJournalEvent->transactionGroup->user);
        $ruleEngine->setAllRules(true);
        $ruleEngine->setTriggerMode(RuleEngine::TRIGGER_UPDATE);
        $journals = $updatedJournalEvent->transactionGroup->transactionJournals;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $ruleEngine->processTransactionJournal($journal);
        }
    }

}
