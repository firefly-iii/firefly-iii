<?php
/**
 * AddTag.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;


use Auth;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;

/**
 * Class AddTag
 *
 * @package FireflyIII\Rules\Actions
 */
class AddTag implements ActionInterface
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
        // journal has this tag maybe?
        $tag = Tag::firstOrCreateEncrypted(['tag' => $this->action->action_value, 'user_id' => Auth::user()->id]);

        $count = $this->journal->tags()->where('id', $tag->id)->count();
        if ($count == 0) {
            $this->journal->tags()->save($tag);
        }

        return true;
    }
}