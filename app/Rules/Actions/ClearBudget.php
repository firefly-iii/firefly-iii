<?php
/**
 * ClearBudget.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Rules\Actions;


use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;

/**
 * Class ClearBudget
 *
 * @package FireflyIII\Rules\Action
 */
class ClearBudget implements ActionInterface
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
        $journal->budgets()->detach();

        return true;
    }
}
