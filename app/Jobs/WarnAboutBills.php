<?php

/*
 * WarnAboutBills.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Events\WarnUserAboutBill;
use FireflyIII\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class WarnAboutBills
 */
class WarnAboutBills implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Carbon $date;
    private bool   $force;

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $date)
    {
        $newDate     = new Carbon();
        $newDate->startOfDay();
        $this->date  = $newDate;

        if (null !== $date) {
            $newDate    = clone $date;
            $newDate->startOfDay();
            $this->date = $newDate;
        }

        $this->force = false;

        app('log')->debug(sprintf('Created new WarnAboutBills("%s")', $this->date->format('Y-m-d')));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app('log')->debug(sprintf('Now at start of WarnAboutBills() job for %s.', $this->date->format('D d M Y')));
        $bills = Bill::all();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            app('log')->debug(sprintf('Now checking bill #%d ("%s")', $bill->id, $bill->name));
            if ($this->hasDateFields($bill)) {
                if ($this->needsWarning($bill, 'end_date')) {
                    $this->sendWarning($bill, 'end_date');
                }
                if ($this->needsWarning($bill, 'extension_date')) {
                    $this->sendWarning($bill, 'extension_date');
                }
            }
        }
        app('log')->debug('Done with handle()');

        // clear cache:
        app('preferences')->mark();
    }

    private function hasDateFields(Bill $bill): bool
    {
        if (false === $bill->active) {
            app('log')->debug('Bill is not active.');

            return false;
        }
        if (null === $bill->end_date && null === $bill->extension_date) {
            app('log')->debug('Bill has no date fields.');

            return false;
        }

        return true;
    }

    private function needsWarning(Bill $bill, string $field): bool
    {
        if (null === $bill->{$field}) {
            return false;
        }
        $diff = $this->getDiff($bill, $field);
        $list = config('firefly.bill_reminder_periods');
        app('log')->debug(sprintf('Difference in days for field "%s" ("%s") is %d day(s)', $field, $bill->{$field}->format('Y-m-d'), $diff));
        if (in_array($diff, $list, true)) {
            return true;
        }

        return false;
    }

    private function getDiff(Bill $bill, string $field): int
    {
        $today  = clone $this->date;
        $carbon = clone $bill->{$field};

        return (int) $today->diffInDays($carbon);
    }

    private function sendWarning(Bill $bill, string $field): void
    {
        $diff = $this->getDiff($bill, $field);
        app('log')->debug('Will now send warning!');
        event(new WarnUserAboutBill($bill, $field, $diff));
    }

    public function setDate(Carbon $date): void
    {
        $newDate    = clone $date;
        $newDate->startOfDay();
        $this->date = $newDate;
    }

    public function setForce(bool $force): void
    {
        $this->force = $force;
    }
}
