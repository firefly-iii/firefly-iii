<?php

/*
 * BudgetObserver.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;

/**
 * Class BudgetObserver
 */
class BudgetObserver
{
    public function deleting(Budget $budget): void
    {
        app('log')->debug('Observe "deleting" of a budget.');

        $repository   = app(AttachmentRepositoryInterface::class);
        $repository->setUser($budget->user);

        foreach ($budget->attachments()->get() as $attachment) {
            $repository->destroy($attachment);
        }
        $budgetLimits = $budget->budgetlimits()->get();

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            // this loop exists so several events are fired.
            $budgetLimit->delete();
        }

        $budget->notes()->delete();
        $budget->autoBudgets()->delete();

        // recalculate available budgets.
    }
}
