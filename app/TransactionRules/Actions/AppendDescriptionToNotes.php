<?php

/*
 * AppendDescriptionToNotes.php
 * Copyright (c) 2022 james@firefly-iii.org
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
use FireflyIII\TransactionRules\Traits\RefreshNotesTrait;

/**
 * Class AppendDescriptionToNotes
 * TODO Can be replaced (and migrated) to action "set notes" with a prefilled expression
 */
class AppendDescriptionToNotes implements ActionInterface
{
    use RefreshNotesTrait;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(private RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        $this->refreshNotes($journal);

        /** @var null|TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, (string) trans('rules.journal_other_user')));

            return false;
        }
        $note   = $object->notes()->first();
        if (null === $note) {
            $note       = new Note();
            $note->noteable()->associate($object);
            $note->text = '';
        }
        $before = $note->text;
        if ('' !== $note->text) {
            $note->text = trim(sprintf("%s  \n%s", $note->text, $object->description));
        }
        if ('' === $note->text) {
            $note->text = (string) $object->description;
        }
        $after  = $note->text;

        // event for audit log entry
        event(new TriggeredAuditLog($this->action->rule, $object, 'update_notes', $before, $after));

        $note->save();

        return true;
    }
}
