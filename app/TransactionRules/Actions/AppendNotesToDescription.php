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

use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Support\Facades\Log;

/**
 * Class AppendNotesToDescription
 */
class AppendNotesToDescription implements ActionInterface
{
    use ConvertsDataTypes;

    private RuleAction $action;

    /**
     * TriggerInterface constructor.
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
        Log::debug('Now in AppendNotesToDescription');
        /** @var TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            Log::error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            return false;
        }
        $note = $object->notes()->first();
        if (null === $note) {
            Log::debug('Journal has no notes.');
            $note = new Note();
            $note->noteable()->associate($object);
            $note->text = '';
        }
        // only append if there is something to append
        if ('' !== $note->text) {
            $before              = $object->description;
            $object->description = trim(sprintf('%s %s', $object->description, (string)$this->clearString($note->text, false)));
            $object->save();
            Log::debug(sprintf('Journal description is updated to "%s".', $object->description));

            event(new TriggeredAuditLog($this->action->rule, $object, 'update_description', $before, $object->description));

            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function has(mixed $key): mixed
    {
        return null;
    }
}
