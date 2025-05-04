<?php

/**
 * RemoveAllTags.php
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
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Facades\DB;

/**
 * Class RemoveAllTags.
 */
class RemoveAllTags implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action)
    {
    }

    public function actOnArray(array $journal): bool
    {
        DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal['transaction_journal_id'])->delete();
        $count  = DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal['transaction_journal_id'])->count();
        if (0 === $count) {
            app('log')->debug(sprintf('RuleAction RemoveAllTags, journal #%d has no tags.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_tags_to_remove')));

            return false;
        }
        app('log')->debug(sprintf('RuleAction RemoveAllTags removed all tags from journal %d.', $journal['transaction_journal_id']));

        /** @var TransactionJournal $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);

        // audit log
        event(new TriggeredAuditLog($this->action->rule, $object, 'clear_all_tags', null, null));

        return true;
    }
}
