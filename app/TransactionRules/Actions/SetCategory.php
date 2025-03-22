<?php

/**
 * SetCategory.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Facades\DB;

/**
 * Class SetCategory.
 */
class SetCategory implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    public function actOnArray(array $journal): bool
    {
        /** @var null|User $user */
        $user            = User::find($journal['user_id']);
        $search          = $this->action->getValue($journal);
        if (null === $user) {
            app('log')->error(sprintf('Journal has no valid user ID so action SetCategory("%s") cannot be applied', $search), $journal);
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_such_journal')));

            return false;
        }

        /** @var CategoryFactory $factory */
        $factory         = app(CategoryFactory::class);
        $factory->setUser($user);
        $category        = $factory->findOrCreate(null, $search);
        if (null === $category) {
            app('log')->debug(
                sprintf(
                    'RuleAction SetCategory could not set category of journal #%d to "%s" because no such category exists.',
                    $journal['transaction_journal_id'],
                    $search
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_category', ['name' => $search])));

            return false;
        }

        app('log')->debug(
            sprintf(
                'RuleAction SetCategory set the category of journal #%d to category #%d ("%s").',
                $journal['transaction_journal_id'],
                $category->id,
                $category->name
            )
        );

        // find previous category
        /** @var TransactionJournal $object */
        $object          = $user->transactionJournals()->find($journal['transaction_journal_id']);
        $oldCategory     = $object->categories()->first();
        $oldCategoryName = $oldCategory?->name;
        if ((int) $oldCategory?->id === $category->id) {
            // event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.already_linked_to_category', ['name' => $category->name])));

            return false;
        }

        DB::table('category_transaction_journal')->where('transaction_journal_id', '=', $journal['transaction_journal_id'])->delete();
        DB::table('category_transaction_journal')->insert(['transaction_journal_id' => $journal['transaction_journal_id'], 'category_id' => $category->id]);

        /** @var TransactionJournal $object */
        $object          = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        event(new TriggeredAuditLog($this->action->rule, $object, 'set_category', $oldCategoryName, $category->name));

        return true;
    }
}
