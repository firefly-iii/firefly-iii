<?php
/**
 * SetNotes.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class SetNotes
 *
 * @package FireflyIII\TransactionRules\Actions
 */
class SetNotes implements ActionInterface
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
        if (is_null($dbNote)) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($journal);
        }
        $oldNotes     = $dbNote->text;
        $dbNote->text = $this->action->action_value;
        $dbNote->save();
        $journal->save();

        Log::debug(sprintf('RuleAction SetNotes changed the notes of journal #%d from "%s" to "%s".', $journal->id, $oldNotes, $this->action->action_value));

        return true;
    }
}
