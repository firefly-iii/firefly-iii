<?php
declare(strict_types=1);
/**
 * ReportSum.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

/**
 * Class ReportSkeleton
 */
class ReportSum extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report on the total sum of transactions. Must be 0.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:report-sum';

    /**
     * Execute the console command.
     *
     * @return int
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
        $start = microtime(true);
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);

        /** @var User $user */
        foreach ($userRepository->all() as $user) {
            $sum = (string)$user->transactions()->sum('amount');
            if (0 !== bccomp($sum, '0')) {
                $message = sprintf('Error: Transactions for user #%d (%s) are off by %s!', $user->id, $user->email, $sum);
                $this->error($message);
            }
            if (0 === bccomp($sum, '0')) {
                $this->info(sprintf('Amount integrity OK for user #%d', $user->id));
            }
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Report on total sum finished in %s seconds', $end));

    }
}
