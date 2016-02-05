<?php
declare(strict_types = 1);
/**
 * RemoveTag.php
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
 * Class RemoveTag
 *
 * @package FireflyIII\Rules\Actions
 */
class RemoveTag implements ActionInterface
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
        // if tag does not exist, no need to continue:
        $name = $this->action->action_value;
        /** @var Tag $tag */
        $tag = Auth::user()->tags()->get()->filter(
            function (Tag $tag) use ($name) {
                return $tag->tag == $name;
            }
        )->first();

        if (!is_null($tag)) {
            $this->journal->tags()->detach([$tag->id]);
        }

        return true;
    }
}
