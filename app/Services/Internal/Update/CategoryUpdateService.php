<?php

/**
 * CategoryUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\User;

/**
 * Class CategoryUpdateService
 */
class CategoryUpdateService
{
    private User $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (auth()->check()) {
            /** @var User $user */
            $user       = auth()->user();
            $this->user = $user;
        }
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @throws \Exception
     */
    public function update(Category $category, array $data): Category
    {
        $oldName = $category->name;
        if (array_key_exists('name', $data)) {
            $category->name = $data['name'];
            $category->save();
            // update triggers and actions
            $this->updateRuleTriggers($oldName, $data['name']);
            $this->updateRuleActions($oldName, $data['name']);
            $this->updateRecurrences($oldName, $data['name']);
        }

        $this->updateNotes($category, $data);

        return $category;
    }

    private function updateRuleTriggers(string $oldName, string $newName): void
    {
        $types    = ['category_is'];
        $triggers = RuleTrigger::leftJoin('rules', 'rules.id', '=', 'rule_triggers.rule_id')
            ->where('rules.user_id', $this->user->id)
            ->whereIn('rule_triggers.trigger_type', $types)
            ->where('rule_triggers.trigger_value', $oldName)
            ->get(['rule_triggers.*'])
        ;
        app('log')->debug(sprintf('Found %d triggers to update.', $triggers->count()));

        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $trigger->trigger_value = $newName;
            $trigger->save();
            app('log')->debug(sprintf('Updated trigger %d: %s', $trigger->id, $trigger->trigger_value));
        }
    }

    private function updateRuleActions(string $oldName, string $newName): void
    {
        $types   = ['set_category'];
        $actions = RuleAction::leftJoin('rules', 'rules.id', '=', 'rule_actions.rule_id')
            ->where('rules.user_id', $this->user->id)
            ->whereIn('rule_actions.action_type', $types)
            ->where('rule_actions.action_value', $oldName)
            ->get(['rule_actions.*'])
        ;
        app('log')->debug(sprintf('Found %d actions to update.', $actions->count()));

        /** @var RuleAction $action */
        foreach ($actions as $action) {
            $action->action_value = $newName;
            $action->save();
            app('log')->debug(sprintf('Updated action %d: %s', $action->id, $action->action_value));
        }
    }

    private function updateRecurrences(string $oldName, string $newName): void
    {
        RecurrenceTransactionMeta::leftJoin('recurrences_transactions', 'rt_meta.rt_id', '=', 'recurrences_transactions.id')
            ->leftJoin('recurrences', 'recurrences.id', '=', 'recurrences_transactions.recurrence_id')
            ->where('recurrences.user_id', $this->user->id)
            ->where('rt_meta.name', 'category_name')
            ->where('rt_meta.value', $oldName)
            ->update(['rt_meta.value' => $newName])
        ;
    }

    /**
     * @throws \Exception
     */
    private function updateNotes(Category $category, array $data): void
    {
        $note         = array_key_exists('notes', $data) ? $data['notes'] : null;
        if (null === $note) {
            return;
        }
        if ('' === $note) {
            $dbNote = $category->notes()->first();
            if (null !== $dbNote) {
                $dbNote->delete();
            }

            return;
        }
        $dbNote       = $category->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($category);
        }
        $dbNote->text = trim($note);
        $dbNote->save();
    }
}
