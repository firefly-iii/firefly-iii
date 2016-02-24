<?php
declare(strict_types = 1);
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

    /** @var RuleAction  */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction         $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action  = $action;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal)
    {
        // journal has this tag maybe?
        $tag = Tag::firstOrCreateEncrypted(['tag' => $this->action->action_value, 'user_id' => Auth::user()->id]);

        $count = $journal->tags()->where('tag_id', $tag->id)->count();
        if ($count == 0) {
            $journal->tags()->save($tag);
        }

        return true;
    }
}
