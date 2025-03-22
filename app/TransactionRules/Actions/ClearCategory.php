<?php

/**
 * ClearCategory.php
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
 * Class ClearCategory.
 */
class ClearCategory implements ActionInterface
{
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
        /** @var TransactionJournal $object */
        $object   = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        $category = $object->categories()->first();
        if (null === $category) {
            app('log')->debug(sprintf('RuleAction ClearCategory, no category in journal #%d.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_already_no_category')));

            return false;
        }

        DB::table('category_transaction_journal')->where('transaction_journal_id', '=', $journal['transaction_journal_id'])->delete();

        event(new TriggeredAuditLog($this->action->rule, $object, 'clear_category', $category->name, null));

        app('log')->debug(sprintf('RuleAction ClearCategory removed all categories from journal #%d.', $journal['transaction_journal_id']));

        return true;
    }
}
