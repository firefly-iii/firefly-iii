<?php
/**
 * RemoveTag.php
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

use FireflyIII\Models\RuleAction;
use FireflyIII\User;
use Log;
use DB;

/**
 * Class RemoveTag.
 */
class RemoveTag implements ActionInterface
{
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
        // if tag does not exist, no need to continue:
        $name = $this->action->action_value;
        $user = User::find($journal['user_id']);
        $tag  = $user->tags()->where('tag', $name)->first();

        if (null !== $tag) {
            Log::debug(sprintf('RuleAction RemoveTag removed tag #%d ("%s") from journal #%d.', $tag->id, $tag->tag, $journal['transaction_journal_id']));
            DB::table('tag_transaction_journal')
              ->where('transaction_journal_id', $journal['transaction_journal_id'])
              ->where('tag_id', $tag->id)
              ->delete();

            return true;
        }
        Log::debug(
            sprintf('RuleAction RemoveTag tried to remove tag "%s" from journal #%d but no such tag exists.', $name, $journal['transaction_journal_id'])
        );

        return true;
    }
}
