<?php
declare(strict_types = 1);
/**
 * ActionInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;

/**
 * Interface ActionInterface
 *
 * @package FireflyIII\Rules\Action
 */
interface ActionInterface
{
    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action);

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal);
}
