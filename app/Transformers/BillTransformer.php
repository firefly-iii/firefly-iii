<?php
/**
 * BillTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

/**
 * Class BillTransformer
 */
class BillTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['attachments', 'notes'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['notes',];
    /** @var Carbon */
    private $end = null;
    /** @var Carbon */
    private $start = null;

    /**
     * BillTransformer constructor.
     *
     * @param Carbon|null $start
     * @param Carbon|null $end
     */
    public function __construct(Carbon $start = null, Carbon $end = null)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param Bill $bill
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAttachments(Bill $bill)
    {
        $attachments = $bill->attachments()->get();

        return $this->collection($attachments, new AttachmentTransformer,'attachment');

    }

    /**
     * @param Bill $bill
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeNotes(Bill $bill)
    {
        $notes = $bill->notes()->get();

        return $this->collection($notes, new NoteTransformer,'note');

    }

    /**
     * @param Bill $bill
     *
     * @return array
     */
    public function transform(Bill $bill): array
    {
        $paidData = $this->paidData($bill);
        $payDates = $this->payDates($bill);

        $data = [
            'id'                  => (int)$bill->id,
            'name'                => $bill->name,
            'match'               => explode(',', $bill->match),
            'amount_min'          => round($bill->amount_min, 2),
            'amount_max'          => round($bill->amount_max, 2),
            'date'                => $bill->date->format('Y-m-d'),
            'repeat_freq'         => $bill->repeat_freq,
            'skip'                => (int)$bill->skip,
            'automatch'           => intval($bill->automatch) === 1,
            'active'              => intval($bill->active) === 1,
            'attachments_count'   => $bill->attachments()->count(),
            'pay_dates'           => $payDates,
            'paid_dates'          => $paidData['paid_dates'],
            'next_expected_match' => $paidData['next_expected_match'],
            'links'               => [
                [
                    'rel' => 'self',
                    'uri' => '/bill/' . $bill->id,
                ],
            ],
        ];

        // todo: attachments, journals, notes


        return $data;

    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     *
     * @param Collection $dates
     * @param Carbon     $default
     *
     * @return Carbon
     */
    protected function lastPaidDate(Collection $dates, Carbon $default): Carbon
    {
        if (0 === $dates->count()) {
            return $default; // @codeCoverageIgnore
        }
        $latest = $dates->first();
        /** @var Carbon $date */
        foreach ($dates as $date) {
            if ($date->gte($latest)) {
                $latest = $date;
            }
        }

        return $latest;
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    protected function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        $start = clone $bill->date;
        while ($start < $date) {
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }

        return $start;
    }

    /**
     * @param Bill $bill
     *
     * @return array
     */
    protected function paidData(Bill $bill): array
    {
        if (is_null($this->start) || is_null($this->end)) {
            return [
                'paid_dates'          => [],
                'next_expected_match' => null,
            ];
        }

        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($bill->user);
        $set    = $repository->getPaidDatesInRange($bill, $this->start, $this->end);
        $simple = $set->map(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );

        // calculate next expected match:
        $lastPaidDate = $this->lastPaidDate($set, $this->start);
        $nextMatch    = clone $bill->date;
        while ($nextMatch < $lastPaidDate) {
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        $end          = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        $journalCount = $repository->getPaidDatesInRange($bill, $nextMatch, $end)->count();
        if ($journalCount > 0) {
            $nextMatch = clone $end;
        }

        return [
            'paid_dates'          => $simple->toArray(),
            'next_expected_match' => $nextMatch->format('Y-m-d'),
        ];
    }

    /**
     * @param Bill $bill
     *
     * @return array
     */
    protected function payDates(Bill $bill): array
    {
        if (is_null($this->start) || is_null($this->end)) {
            return [];
        }
        $set          = new Collection;
        $currentStart = clone $this->start;
        while ($currentStart <= $this->end) {
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->end) {
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;
        }
        $simple = $set->map(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );

        return $simple->toArray();
    }
}