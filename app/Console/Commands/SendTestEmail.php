<?php


/*
 * SendTestEmail.php
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

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Events\Test\OwnerTestsNotificationChannel;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Console\Command;

class SendTestEmail extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesAccessToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:send-test-email
                            {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = $this->getUser();
        if (!$user->hasRole('owner')) {
            $this->friendlyError((string)trans('firefly.must_be_owner'));

            return Command::FAILURE;
        }

        /** @var int $lastNotification */
        $lastNotification = FireflyConfig::get('last_test_notification', 123)->data;
        if (time() - $lastNotification < 120) {
            $this->friendlyError((string)trans('firefly.test_rate_limited'));

            return Command::FAILURE;
        }


        $owner = new OwnerNotifiable();
        event(new OwnerTestsNotificationChannel('email', $owner));
        FireflyConfig::set('last_test_notification',time());
        return Command::SUCCESS;
    }
}
