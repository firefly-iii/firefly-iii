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
use Log;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    /** @var BillRepositoryInterface */
    private $repository;

    /**
     * BillTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(BillRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform the bill.
     *
     * @param Bill $bill
     *
     * @return array
     */
    public function transform(Bill $bill): array
    {
        $paidData = $this->paidData($bill);
        $payDates = $this->payDates($bill);
        $currency = $bill->transactionCurrency;
        $notes    = $this->repository->getNoteText($bill);
        $notes    = '' === $notes ? null : $notes;
        $this->repository->setUser($bill->user);
        $data = [
            'id'                      => (int)$bill->id,
            'created_at'              => $bill->created_at->toAtomString(),
            'updated_at'              => $bill->updated_at->toAtomString(),
            'currency_id'             => $bill->transaction_currency_id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'name'                    => $bill->name,
            'amount_min'              => round((float)$bill->amount_min, $currency->decimal_places),
            'amount_max'              => round((float)$bill->amount_max, $currency->decimal_places),
            'date'                    => $bill->date->format('Y-m-d'),
            'repeat_freq'             => $bill->repeat_freq,
            'skip'                    => (int)$bill->skip,
            'active'                  => $bill->active,
            'notes'                   => $notes,
            'next_expected_match'     => $paidData['next_expected_match'],
            'pay_dates'               => $payDates,
            'paid_dates'              => $paidData['paid_dates'],
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];

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
     * @return Carbon
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
     * Get the data the bill was paid and predict the next expected match.
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function paidData(Bill $bill): array
    {
        Log::debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            Log::debug('parameters are NULL, return empty array');

            return [
                'paid_dates'          => [],
                'next_expected_match' => null,
            ];
        }

        $set = $this->repository->getPaidDatesInRange($bill, $this->parameters->get('start'), $this->parameters->get('end'));
        Log::debug(sprintf('Count %d entries in getPaidDatesInRange()', $set->count()));
        $simple = $set->map(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );

        // calculate next expected match:
        $lastPaidDate = $this->lastPaidDate($set, $this->parameters->get('start'));
        $nextMatch    = clone $bill->date;
        while ($nextMatch < $lastPaidDate) {
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        $end          = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        $journalCount = $this->repository->getPaidDatesInRange($bill, $nextMatch, $end)->count();
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
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            return [];
        }
        $set          = new Collection;
        $currentStart = clone $this->parameters->get('start');
        while ($currentStart <= $this->parameters->get('end')) {
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
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
