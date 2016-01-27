<?php
/**
 * ClearCategory.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;


use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;

/**
 * Class ClearCategory
 *
 * @package FireflyIII\Rules\Action
 */
class ClearCategory implements ActionInterface
{

    private $action;
    private $journal;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction         $action
     * @param TransactionJournal $journal
     */
    public function __construct(RuleAction $action, TransactionJournal $journal)
    {
        $this->action  = $action;
        $this->journal = $journal;
    }

    /**
     * @return bool
     */
    public function act()
    {
        $this->journal->categories()->detach();

        return true;
    }
}