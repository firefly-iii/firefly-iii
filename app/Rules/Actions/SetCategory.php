<?php
/**
 * SetCategory.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Rules\Actions;


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
        $name     = $this->action->action_value;
        $category = Category::firstOrCreateEncrypted(['name' => $name, 'user_id' => $journal->user->id]);
        $journal->categories()->sync([$category->id]);

        Log::debug(sprintf('RuleAction SetCategory set the category of journal #%d to budget #%d ("%s").', $journal->id, $category->id, $category->name));

        return true;
    }
}
