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

use Exception;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class ClearNotes.
 */
class ClearNotes implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
    }

    /**
     * Remove notes
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws Exception
     */
    public function act(TransactionJournal $journal): bool
    {
        Log::debug(sprintf('RuleAction ClearNotes removed all notes.'));
        $notes = $journal->notes()->get();
        /** @var Note $note */
        foreach ($notes as $note) {
            $note->delete();
        }
        $journal->touch();

        return true;
    }
}
