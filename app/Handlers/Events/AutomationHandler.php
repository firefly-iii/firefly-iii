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
use FireflyIII\Mail\ReportNewJournalsMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mail;

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
     * @return bool
     */
    public function reportJournals(RequestedReportOnJournals $event): bool
    {
        $sendReport = config('firefly.send_report_journals');

        if (false === $sendReport) {
            return true; // @codeCoverageIgnore
        }

        Log::debug('In reportJournals.');
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull($event->userId);
        if (null !== $user && 0 !== $event->groups->count()) {
            try {
                Log::debug('Trying to mail...');
                Mail::to($user->email)->send(new ReportNewJournalsMail($user->email, '127.0.0.1', $event->groups));
                // @codeCoverageIgnoreStart
            } catch (Exception $e) {
                Log::debug('Send message failed! :(');
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
            // @codeCoverageIgnoreEnd
            Log::debug('Done!');
        }

        return true;
    }
}
