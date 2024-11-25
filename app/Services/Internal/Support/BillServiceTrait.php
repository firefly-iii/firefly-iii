<?php

/**
 * BillServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;

/**
 * Trait BillServiceTrait
 */
trait BillServiceTrait
{
    public function updateBillActions(Bill $bill, string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $ruleIds = $bill->user->rules()->get(['id'])->pluck('id')->toArray();
        $set     = RuleAction::whereIn('rule_id', $ruleIds)
            ->where('action_type', 'link_to_bill')
            ->where('action_value', $oldName)->get()
        ;

        /** @var RuleAction $ruleAction */
        foreach ($set as $ruleAction) {
            app('log')->debug(sprintf('Updated rule action #%d to search for new bill name "%s"', $ruleAction->id, $newName));
            $ruleAction->action_value = $newName;
            $ruleAction->save();
        }
    }

    public function updateNote(Bill $bill, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $bill->notes()->first();
            if (null !== $dbNote) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote       = $bill->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($bill);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
