<?php

/**
 * SetDescription.php
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
use Illuminate\Support\Facades\Log;

/**
 * Class SetDescription.
 */
class SetDescription implements ActionInterface
{
    use RefreshNotesTrait;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(private RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        $this->refreshNotes($journal);

        /** @var TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        $before = $object->description;
        $after  = $this->action->getValue($journal);

        // replace newlines.
        $after  = trim(str_replace(["\r", "\n", "\t", "\036", "\025"], '', $after));

        if('' === $after) {
            Log::warning('Action resulted in an empty description, reset to default value.');
            $after = '(no description)';
        }

        DB::table('transaction_journals')
            ->where('id', '=', $journal['transaction_journal_id'])
            ->update(['description' => $after])
        ;

        app('log')->debug(
            sprintf(
                'RuleAction SetDescription changed the description of journal #%d from "%s" to "%s".',
                $journal['transaction_journal_id'],
                $journal['description'],
                $after
            )
        );
        $object->refresh();
        event(new TriggeredAuditLog($this->action->rule, $object, 'update_description', $before, $after));

        return true;
    }
}
