<?php
/**
 * PrependNotes.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class PrependNotes
 *
 * @package FireflyIII\TransactionRules\Actions
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
        $notes = $journal->getMeta('notes');
        Log::debug(sprintf('RuleAction PrependNotes prepended "%s" with "%s".', $notes, $this->action->action_value));
        $notes = $this->action->action_value . $notes;
        $journal->setMeta('notes', $notes);
        $journal->save();

        return true;
    }
}
