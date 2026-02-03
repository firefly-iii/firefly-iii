<?php

/**
 * UpdatedGroupEventHandler.php
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PeriodStatistic\PeriodStatisticRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdatedGroupEventHandler
 */
class UpdatedGroupEventHandler
{
    /**
     * TODO duplicate
     */
    private function removePeriodStatistics(UpdatedTransactionGroup $event): void
    {
        /** @var PeriodStatisticRepositoryInterface $repository */
        $repository = app(PeriodStatisticRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        foreach ($event->transactionGroup->transactionJournals as $journal) {
            $source = $journal->transactions()->where('amount', '<', '0')->first();
            $dest   = $journal->transactions()->where('amount', '>', '0')->first();
            if (null !== $source) {
                $repository->deleteStatisticsForModel($source->account, $journal->date);
            }
            if (null !== $dest) {
                $repository->deleteStatisticsForModel($dest->account, $journal->date);
            }

            $categories = $journal->categories;
            $tags       = $journal->tags;
            $budgets    = $journal->budgets;

            foreach ($categories as $category) {
                $repository->deleteStatisticsForModel($category, $journal->date);
            }
            foreach ($tags as $tag) {
                $repository->deleteStatisticsForModel($tag, $journal->date);
            }
            foreach ($budgets as $budget) {
                $repository->deleteStatisticsForModel($budget, $journal->date);
            }
            if (0 === $categories->count()) {
                $repository->deleteStatisticsForPrefix($journal->userGroup, 'no_category', $journal->date);
            }
            if (0 === $budgets->count()) {
                $repository->deleteStatisticsForPrefix($journal->userGroup, 'no_budget', $journal->date);
            }
        }
    }



}
