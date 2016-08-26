<?php
/**
 * RemoveTag.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
        // if tag does not exist, no need to continue:
        $name = $this->action->action_value;
        /** @var Tag $tag */
        $tag = $journal->user->tags()->get()->filter(
            function (Tag $tag) use ($name) {
                return $tag->tag == $name;
            }
        )->first();

        if (!is_null($tag)) {
            $journal->tags()->detach([$tag->id]);
        }

        return true;
    }
}
