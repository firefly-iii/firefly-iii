<?php

declare(strict_types=1);

/*
 * MailsNewTransactionsReport.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Events\Model\TransactionGroup\TransactionGroupsRequestedReporting;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\TransactionCreation;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MailsNewTransactionsReport implements ShouldQueue
{
    public function handle(TransactionGroupsRequestedReporting $event): void
    {
        Log::debug('In MailsNewTransactionsReport.');

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find($event->userId);

        /** @var bool $sendReport */
        $sendReport = Preferences::getForUser($user, 'notification_transaction_creation', false)->data;

        if (false === $sendReport) {
            Log::debug('Not sending report, because config says so.');

            return;
        }

        if (null === $user || 0 === $event->groups->count()) {
            Log::debug('No transaction groups in event, nothing to email about.');

            return;
        }
        Log::debug('Continue with message!');

        // transform groups into array:
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $groups      = [];

        /** @var TransactionGroup $group */
        foreach ($event->groups as $group) {
            $groups[] = $transformer->transformObject($group);
        }

        NotificationSender::send($user, new TransactionCreation($groups));
        Log::debug('If there is no error above this line, message was sent.');
    }
}
