<?php
declare(strict_types=1);
/*
 * MoveDescriptionToNotes.php
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

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Facades\Log;

/**
 * Class MoveDescriptionToNotes
 */
class MoveDescriptionToNotes implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     *
     * @codeCoverageIgnore
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
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $journal) {
            Log::error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            return false;
        }
        $note = $journal->notes()->first();
        if (null === $note) {
            $note = new Note;
            $note->noteable()->associate($journal);
            $note->text = '';
        }
        $before            = $note->text;
        $beforeDescription = $journal->description;
        if ('' !== $note->text) {
            $note->text           = trim(sprintf("%s  \n%s", $note->text, $journal->description));
            $journal->description = '(no description)';
        }
        if ('' === $note->text) {
            $note->text           = (string) $journal->description;
            $journal->description = '(no description)';
        }
        $after = $note->text;

        event(new TriggeredAuditLog($this->action->rule, $journal, 'update_description', $beforeDescription, $journal->description));
        event(new TriggeredAuditLog($this->action->rule, $journal, 'update_notes', $before, $after));

        $note->save();
        $journal->save();
        return true;
    }
}
