<?php
/**
 * BillServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use Illuminate\Support\Collection;
use Log;

/**
 * Trait BillServiceTrait
 * @codeCoverageIgnore
 */
trait BillServiceTrait
{

    /**
     * @param Bill   $bill
     * @param string $oldName
     * @param string $newName
     */
    public function updateBillActions(Bill $bill, string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $ruleIds = $bill->user->rules()->get(['id'])->pluck('id')->toArray();
        /** @var Collection $set */
        $set = RuleAction::whereIn('rule_id', $ruleIds)
                         ->where('action_type', 'link_to_bill')
                         ->where('action_value', $oldName)->get();

        /** @var RuleAction $ruleAction */
        foreach ($set as $ruleAction) {
            $ruleAction->action_value = $newName;
            $ruleAction->save();
        }
    }


    /**
     * @param Bill   $bill
     * @param string $note
     *
     * @return bool
     */
    public function updateNote(Bill $bill, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $bill->notes()->first();
            if (null !== $dbNote) {
                try {
                    $dbNote->delete();
                } catch (Exception $e) {
                    Log::debug(sprintf('Error deleting note: %s', $e->getMessage()));
                }
            }

            return true;
        }
        $dbNote = $bill->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($bill);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }

}
