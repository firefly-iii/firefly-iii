<?php

/**
 * ClearNotes.php
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

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Facades\DB;

/**
 * Class ClearNotes.
 */
class ClearNotes implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        /** @var TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);

        /** @var null|Note $notes */
        $notes  = $object->notes()->first();
        if (null === $notes) {
            app('log')->debug(sprintf('RuleAction ClearNotes, journal #%d has no notes.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_already_no_notes')));

            return false;
        }
        $before = $notes->text;

        DB::table('notes')
            ->where('noteable_id', $journal['transaction_journal_id'])
            ->where('noteable_type', TransactionJournal::class)
            ->delete()
        ;
        app('log')->debug(sprintf('RuleAction ClearNotes removed all notes from journal #%d.', $journal['transaction_journal_id']));

        event(new TriggeredAuditLog($this->action->rule, $object, 'clear_notes', $before, null));

        return true;
    }
}
