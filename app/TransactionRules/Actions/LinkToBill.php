<?php
/**
 * LinkToBill.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Log;

/**
 * Class LinkToBill.
 */
class LinkToBill implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @codeCoverageIgnore
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * Set bill to be X.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($this->action->rule->user);
        $billName = (string)$this->action->action_value;
        $bill     = $repository->findByName($billName);

        if (null !== $bill && $journal->transactionType->type === TransactionType::WITHDRAWAL) {
            $journal->bill()->associate($bill);
            $journal->save();
            Log::debug(sprintf('RuleAction LinkToBill set the bill of journal #%d to bill #%d ("%s").', $journal->id, $bill->id, $bill->name));

            return true;
        }

        Log::error(sprintf('RuleAction LinkToBill could not set the bill of journal #%d to bill "%s": no such bill found!', $journal->id, $billName));


        return false;
    }
}
