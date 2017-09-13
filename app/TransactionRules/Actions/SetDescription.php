<?php
/**
 * SetDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
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
 * Class SetDescription
 *
 * @package FireflyIII\TransactionRules\Actions
 */
class SetDescription implements ActionInterface
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
        $oldDescription = $journal->description;

        $journal->description = $this->action->action_value;
        $journal->save();

        Log::debug(
            sprintf(
                'RuleAction SetDescription changed the description of journal #%d from "%s" to "%s".', $journal->id,
                $oldDescription,
                $this->action->action_value
            )
        );

        return true;
    }
}
