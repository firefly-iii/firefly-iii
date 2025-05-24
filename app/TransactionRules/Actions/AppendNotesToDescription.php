<?php

/*
 * AppendNotesToDescription.php
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
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\TransactionRules\Traits\RefreshNotesTrait;

/**
 * Class AppendNotesToDescription
 * TODO Can be replaced (and migrated) to action "set description" with a prefilled expression
 */
class AppendNotesToDescription implements ActionInterface
{
    use ConvertsDataTypes;
    use RefreshNotesTrait;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(private RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        app('log')->debug('Now in AppendNotesToDescription');
        $this->refreshNotes($journal);

        /** @var null|TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_other_user')));

            return false;
        }
        $note   = $object->notes()->first();
        if (null === $note) {
            app('log')->debug('Journal has no notes.');
            $note       = new Note();
            $note->noteable()->associate($object);
            $note->text = '';
        }
        // only append if there is something to append
        if ('' !== $note->text) {
            $before              = $object->description;
            $object->description = trim(sprintf('%s %s', $object->description, (string) $this->clearString($note->text)));
            $object->save();
            app('log')->debug(sprintf('Journal description is updated to "%s".', $object->description));

            event(new TriggeredAuditLog($this->action->rule, $object, 'update_description', $before, $object->description));

            return true;
        }
        event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.new_notes_empty')));

        return false;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return null;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function has(mixed $key): mixed
    {
        return null;
    }
}
