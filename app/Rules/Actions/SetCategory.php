<?php
/**
 * SetCategory.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;


use Auth;
use FireflyIII\Models\Category;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class SetCategory
 *
 * @package FireflyIII\Rules\Action
 */
class SetCategory implements ActionInterface
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
        $name     = $this->action->action_value;
        $category = Category::firstOrCreateEncrypted(['name' => $name, 'user_id' => Auth::user()->id]);
        Log::debug('Will set category "' . $name . '" (#' . $category->id . ') on journal #' . $this->journal->id . '.');
        $this->journal->categories()->sync([$category->id]);

        return true;
    }
}