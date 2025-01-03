<?php

/*
 * MoveNotesToDescription.php
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
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Request\ConvertsDataTypes;

/**
 * Class MoveNotesToDescription
 */

/**
 * Class MoveNotesToDescription
 * TODO Can be replaced (and migrated) to action "set notes" with a prefilled expression
 */
class MoveNotesToDescription implements ActionInterface
{
    use ConvertsDataTypes;

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
        /** @var null|TransactionJournal $object */
        $object              = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_other_user')));

            return false;
        }
        $note                = $object->notes()->first();
        if (null === $note) {
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_notes_to_move')));

            // nothing to move, return null
            return false;
        }
        if ('' === $note->text) {
            // nothing to move, return null
            $note->delete();
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_notes_to_move')));

            return false;
        }
        $before              = $object->description;
        $beforeNote          = $note->text;
        $object->description = (string) $this->clearString($note->text);
        $object->save();
        $note->delete();

        event(new TriggeredAuditLog($this->action->rule, $object, 'update_description', $before, $object->description));
        event(new TriggeredAuditLog($this->action->rule, $object, 'clear_notes', $beforeNote, null));

        return true;
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
