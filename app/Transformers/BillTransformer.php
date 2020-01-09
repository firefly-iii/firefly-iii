<?php
/**
 * BillTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
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
        $latest = $dates->first()->date;
        /** @var TransactionJournal $date */
        foreach ($dates as $journal) {
            if ($journal->date->gte($latest)) {
                $latest = $journal->date;
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
        Log::debug(sprintf('Now in nextDateMatch(%d, %s)', $bill->id, $date->format('Y-m-d')));
        $start = clone $bill->date;
        Log::debug(sprintf('Bill start date is %s', $start->format('Y-m-d')));
        while ($start < $date) {
            Log::debug(
                sprintf(
                    '%s (bill start date) < %s (given date) so we jump ahead one period (with a skip maybe).', $start->format('Y-m-d'), $date->format('Y-m-d')
                )
            );
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }
        Log::debug(sprintf('End of loop, bill start date is now %s', $start->format('Y-m-d')));

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

        // calculate next expected match:
        $lastPaidDate = $this->lastPaidDate($set, $this->parameters->get('start'));
        $nextMatch    = clone $bill->date;
        while ($nextMatch < $lastPaidDate) {
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        $end          = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        if ($set->count() > 0) {
            $nextMatch = clone $end;
        }
        $result = [];
        foreach ($set as $entry) {
            $result[] = [
                'transaction_group_id'   => (int)$entry->transaction_group_id,
                'transaction_journal_id' => (int)$entry->id,
                'date'                   => $entry->date->format('Y-m-d'),
            ];
        }

        return [
            'paid_dates'          => $result,
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
        Log::debug(sprintf('Now in payDates() for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            Log::debug('No start or end date, give empty array.');

            return [];
        }
        Log::debug(
            sprintf(
                'Start date is %s, end is %s', $this->parameters->get('start')->format('Y-m-d'),
                $this->parameters->get('end')->format('Y-m-d')
            )
        );
        $set          = new Collection;
        $currentStart = clone $this->parameters->get('start');
        $loop         = 0;
        while ($currentStart <= $this->parameters->get('end')) {
            Log::debug(
                sprintf(
                    'In loop #%d, where %s (start param) <= %s (end param).', $loop, $currentStart->format('Y-m-d'),
                    $this->parameters->get('end')->format('Y-m-d')
                )
            );
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            Log::debug(sprintf('Next expected match is %s', $nextExpectedMatch->format('Y-m-d')));
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
                Log::debug(
                    sprintf('%s is > %s, so were not going to use it.', $nextExpectedMatch->format('Y-m-d'), $this->parameters->get('end')->format('Y-m-d'))
                );
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            Log::debug(sprintf('Add next expected match (%s) to set because its in the current start/end range, which now contains %d item(s)', $nextExpectedMatch->format('Y-m-d'), $set->count()));
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;
            $loop++;
        }
        $simple = $set->map(
            static function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );
        $array = $simple->toArray();
        Log::debug(sprintf('Loop has ended after %d loops', $loop), $array);

        return $array;
    }
}
