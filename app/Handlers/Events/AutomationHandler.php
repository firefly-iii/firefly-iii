<?php
/**
 * AutomationHandler.php
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

use Exception;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Notifications\User\TransactionCreation;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Class AutomationHandler
 */
class AutomationHandler
{
    /**
     * Respond to the creation of X journals.
     *
     * @param RequestedReportOnJournals $event
     *
     * @throws FireflyException
     */
    public function reportJournals(RequestedReportOnJournals $event): void
    {
        Log::debug('In reportJournals.');
        $sendReport = config('firefly.send_report_journals');
        if (false === $sendReport) {
            return;
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find($event->userId);
        if (null === $user || 0 === $event->groups->count()) {
            return;
        }

        // transform groups into array:
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $groups      = [];
        /** @var TransactionGroup $group */
        foreach ($event->groups as $group) {
            $groups[] = $transformer->transformObject($group);
        }
        try {
            Notification::send($user, new TransactionCreation($groups));
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                Log::warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');
                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                Log::warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');
                return;
            }
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
