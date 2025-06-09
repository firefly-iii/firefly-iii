<?php

/**
 * ReportSum.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Console\Command;

class ReportsSums extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Report on the total sum of transactions. Must be 0.';
    protected $signature   = 'integrity:total-sums';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->reportSum();

        return 0;
    }

    /**
     * Reports for each user when the sum of their transactions is not zero.
     */
    private function reportSum(): void
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);

        /** @var User $user */
        foreach ($userRepository->all() as $user) {
            $sum     = (string) $user->transactions()->selectRaw('SUM(amount) as total')->value('total');
            $foreign = (string) $user->transactions()->selectRaw('SUM(foreign_amount) as total')->value('total');
            $sum     = '' === $sum ? '0' : $sum;
            $foreign = '' === $foreign ? '0' : $foreign;
            $sum     = Steam::floatalize($sum);
            $foreign = Steam::floatalize($foreign);
            $total   = bcadd($sum, $foreign);

            if (0 !== bccomp($total, '0')) {
                $message = sprintf('Error: Transactions for user #%d (%s) are off by %s!', $user->id, $user->email, $total);
                $this->friendlyError($message);
            }
            if (0 === bccomp($total, '0')) {
                $this->friendlyPositive(sprintf('Amount integrity OK for user #%d', $user->id));
            }
        }
    }
}
