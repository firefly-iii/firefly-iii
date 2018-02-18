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
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

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
    protected $availableIncludes = ['attachments', 'transactions', 'user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * BillTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Include any attachments.
     *
     * @param Bill $bill
     *
     * @codeCoverageIgnore
     * @return FractalCollection
     */
    public function includeAttachments(Bill $bill): FractalCollection
    {
        $attachments = $bill->attachments()->get();

        return $this->collection($attachments, new AttachmentTransformer($this->parameters), 'attachments');
    }

    /**
     * Include any transactions.
     *
     * @param Bill $bill
     *
     * @codeCoverageIgnore
     * @return FractalCollection
     */
    public function includeTransactions(Bill $bill): FractalCollection
    {
        $pageSize = intval(app('preferences')->getForUser($bill->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($bill->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setBills(new Collection([$bill]));
        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $journals = $collector->getJournals();

        return $this->collection($journals, new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * Include the user.
     *
     * @param Bill $bill
     *
     * @codeCoverageIgnore
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Bill $bill): Item
    {
        return $this->item($bill->user, new UserTransformer($this->parameters), 'users');
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
        $data     = [
            'id'                  => (int)$bill->id,
            'updated_at'          => $bill->updated_at->toAtomString(),
            'created_at'          => $bill->created_at->toAtomString(),
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
            'notes'               => null,
            'links'               => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];
        /** @var Note $note */
        $note = $bill->notes()->first();
        if (!is_null($note)) {
            $data['notes'] = $note->text;
        }

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
     * Get the data the bill was paid and predict the next expected match.
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function paidData(Bill $bill): array
    {
        Log::debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (is_null($this->parameters->get('start')) || is_null($this->parameters->get('end'))) {
            Log::debug('parameters are NULL, return empty array');

            return [
                'paid_dates'          => [],
                'next_expected_match' => null,
            ];
        }

        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($bill->user);
        $set = $repository->getPaidDatesInRange($bill, $this->parameters->get('start'), $this->parameters->get('end'));
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
        if (is_null($this->parameters->get('start')) || is_null($this->parameters->get('end'))) {
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