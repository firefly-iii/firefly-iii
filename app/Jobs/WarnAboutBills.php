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
use FireflyIII\Events\Model\Bill\WarnUserAboutBill;
use FireflyIII\Events\Model\Bill\WarnUserAboutOverdueSubscriptions;
use FireflyIII\Models\Bill;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\JsonApi\Enrichments\SubscriptionEnrichment;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        if ($date instanceof Carbon) {
            $newDate    = clone $date;
            $newDate->startOfDay();
            $this->date = $newDate;
        }

        $this->force = false;

        Log::debug(sprintf('Created new WarnAboutBills("%s")', $this->date->format('Y-m-d')));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug(sprintf('Now at start of WarnAboutBills() job for %s.', $this->date->format('D d M Y')));
        foreach (User::all() as $user) {
            $bills   = $user->bills()->where('active', true)->get();
            $overdue = [];

            /** @var Bill $bill */
            foreach ($bills as $bill) {
                Log::debug(sprintf('Now checking bill #%d ("%s")', $bill->id, $bill->name));
                $dates = $this->getDates($bill);
                if ($this->needsOverdueAlert($dates)) {
                    $overdue[] = ['bill' => $bill, 'dates' => $dates];
                }
                if ($this->hasDateFields($bill)) {
                    if ($this->needsWarning($bill, 'end_date')) {
                        $this->sendWarning($bill, 'end_date');
                    }
                    if ($this->needsWarning($bill, 'extension_date')) {
                        $this->sendWarning($bill, 'extension_date');
                    }
                }
            }
            $this->sendOverdueAlerts($user, $overdue);
        }
        Log::debug('Done with handle()');

    }

    private function hasDateFields(Bill $bill): bool
    {
        if (false === $bill->active) {
            Log::debug('Bill is not active.');

            return false;
        }
        if (null === $bill->end_date && null === $bill->extension_date) {
            Log::debug('Bill has no date fields.');

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
        Log::debug(sprintf('Difference in days for field "%s" ("%s") is %d day(s)', $field, $bill->{$field}->format('Y-m-d'), $diff));
        if (in_array($diff, $list, true)) {
            return true;
        }

        return false;
    }

    private function getDiff(Bill $bill, string $field): int
    {
        $today  = clone $this->date;
        $carbon = clone $bill->{$field};

        return (int)$today->diffInDays($carbon);
    }

    private function sendWarning(Bill $bill, string $field): void
    {
        $diff = $this->getDiff($bill, $field);
        Log::debug('Will now send warning!');
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

    private function getDates(Bill $bill): array
    {
        $start      = clone $this->date;
        $start      = Navigation::startOfPeriod($start, $bill->repeat_freq);
        $end        = clone $start;
        $end        = Navigation::endOfPeriod($end, $bill->repeat_freq);
        $enrichment = new SubscriptionEnrichment();
        $enrichment->setUser($bill->user);
        $enrichment->setStart($start);
        $enrichment->setEnd($end);
        $single     = $enrichment->enrichSingle($bill);

        return [
            'pay_dates'  => $single->meta['pay_dates'] ?? [],
            'paid_dates' => $single->meta['paid_dates'] ?? [],
        ];
    }

    private function needsOverdueAlert(array $dates): bool
    {
        $count    = count($dates['pay_dates']) - count($dates['paid_dates']);
        if (0 === $count || 0 === count($dates['pay_dates'])) {
            return false;
        }
        // the earliest date in the list of pay dates must be 48hrs or more ago.
        $earliest = new Carbon($dates['pay_dates'][0]);
        $earliest->startOfDay();
        Log::debug(sprintf('Earliest expected pay date is %s', $earliest->toAtomString()));
        $diff     = $earliest->diffInDays($this->date);
        Log::debug(sprintf('Difference in days is %s', $diff));
        if ($diff < 2) {
            return false;
        }

        return true;
    }

    private function sendOverdueAlerts(User $user, array $overdue): void
    {
        if (count($overdue) > 0) {
            Log::debug(sprintf('Will now send warning about overdue bill for user #%d.', $user->id));
            event(new WarnUserAboutOverdueSubscriptions($user, $overdue));
        }
    }
}
