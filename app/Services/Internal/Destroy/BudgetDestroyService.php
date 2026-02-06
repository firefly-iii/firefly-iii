<?php

/**
 * BudgetDestroyService.php
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

namespace FireflyIII\Services\Internal\Destroy;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\Budget\DestroyedBudget;
use FireflyIII\Events\Model\Budget\DestroyingBudget;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Support\Models\AvailableBudgetCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class BudgetDestroyService
 */
class BudgetDestroyService
{
    public function destroy(Budget $budget): void
    {
        // first trigger delete event so the webhook messages can get generated.
        event(new DestroyingBudget($budget));

        // remove attachments
        $repository = app(AttachmentRepositoryInterface::class);
        $repository->setUser($budget->user);

        /** @var Attachment $attachment */
        foreach ($budget->attachments()->get() as $attachment) {
            $repository->destroy($attachment);
        }

        // get budget limits, recalculate for period each time, then delete .
        $budgetLimits = $budget->budgetlimits()->get();

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            // need to recalculate all available budgets for this user.
            $calculator = new AvailableBudgetCalculator();
            $calculator->setUser($budget->user);
            $calculator->setStart($budgetLimit->start_date);
            $calculator->setEnd($budgetLimit->end_date);
            $calculator->setCreate(false);
            $calculator->setCurrency($budgetLimit->transactionCurrency);

            // delete budget limit so the recalculation ignores it.
            $budgetLimit->delete();
            $calculator->recalculateByRange();
        }
        // delete notes and other objects.
        $budget->notes()->delete();
        $budget->autoBudgets()->delete();

        // also delete all relations between categories and transaction journals:
        DB::table('budget_transaction_journal')->where('budget_id', $budget->id)->delete();

        // also delete all relations between categories and transactions:
        DB::table('budget_transaction')->where('budget_id', $budget->id)->delete();

        $budget->delete();
        event(new DestroyedBudget());
    }
}
