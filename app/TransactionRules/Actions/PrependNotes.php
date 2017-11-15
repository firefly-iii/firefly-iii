<?php
/**
 * PrependNotes.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class PrependNotes.
 */
class PrependNotes implements ActionInterface
{
    private $action;

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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $dbNote = $journal->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($journal);
        }
        $notes = $dbNote->text;
        Log::debug(sprintf('RuleAction PrependNotes prepended "%s" with "%s".', $notes, $this->action->action_value));
        $notes        = $this->action->action_value . $notes;
        $dbNote->text = $notes;
        $dbNote->save();
        $journal->save();

        return true;
    }
}
