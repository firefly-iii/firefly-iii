<?php

/**
 * AppendDescription.php
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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Traits\RefreshNotesTrait;
use Illuminate\Support\Facades\DB;

/**
 * Class AppendDescription.
 * TODO Can be replaced (and migrated) to action "set description" with a prefilled expression
 */
class AppendDescription implements ActionInterface
{
    use RefreshNotesTrait;

    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    public function actOnArray(array $journal): bool
    {
        $this->refreshNotes($journal);
        $append      = $this->action->getValue($journal);
        $description = sprintf('%s %s', $journal['description'], $append);
        DB::table('transaction_journals')->where('id', $journal['transaction_journal_id'])->limit(1)->update(['description' => $description]);

        // event for audit log entry
        /** @var TransactionJournal $object */
        $object      = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        event(new TriggeredAuditLog($this->action->rule, $object, 'update_description', $journal['description'], $description));

        return true;
    }
}
