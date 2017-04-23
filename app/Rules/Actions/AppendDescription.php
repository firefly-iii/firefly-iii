<?php
/**
 * AppendDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Rules\Actions;

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class AppendDescription
 *
 * @package FireflyIII\Rules\Actions
 */
class AppendDescription implements ActionInterface
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
        Log::debug(sprintf('RuleAction AppendDescription appended "%s" to "%s".', $this->action->action_value, $journal->description));
        $journal->description = $journal->description . $this->action->action_value;
        $journal->save();

        return true;
    }
}
