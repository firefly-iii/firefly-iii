<?php

declare(strict_types=1);

/*
 * ExplainAvailableBudget.php
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

namespace FireflyIII\Console\Commands\Explain;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Console\Command;

class ExplainAvailableBudget extends Command
{
    use VerifiesAccessToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'explain:available-budget
                                {--date=now : A date formatted YYYY-MM-DD or the word "now"}
                                {--user=1 : The user ID.}
                                {--token= : The user\'s access token.}
   ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explains why the available budget amount is what it is.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date  = $this->getDate((string) $this->option('date'));
        $range = Preferences::getForUser($this->getUser(), 'viewRange', '1M')->data ?? '1M';
        $title = Navigation::periodShow($date, $range);
        $this->line('This command explains why the "available" budget bar at the top of your /budget bar means.');
        $this->line(sprintf(
            'You submitted date %s and your settings show a %s period, so this explanation concerns the period "%s".',
            $date->format('Y-m-d'),
            $range,
            $title
        ));

        return Command::SUCCESS;
    }

    private function getDate(string $param): Carbon
    {
        if ('now' === $param) {
            return today();
        }

        try {
            $date = Carbon::parse($param);
        } catch (InvalidFormatException) {
            $this->warn('Invalid date given. Fall back to today\'s date.');

            return today();
        }

        return $date;
    }
}
