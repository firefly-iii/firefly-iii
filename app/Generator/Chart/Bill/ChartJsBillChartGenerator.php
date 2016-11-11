<?php
/**
 * ChartJsBillChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Bill;

use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Support\ChartColour;
use Illuminate\Support\Collection;

/**
 * Class ChartJsBillChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Bill
 */
class ChartJsBillChartGenerator implements BillChartGeneratorInterface
{

    /**
     * @param string $paid
     * @param string $unpaid
     *
     * @return array
     */
    public function frontpage(string $paid, string $unpaid): array
    {
        $data = [
            'datasets' => [
                [
                    'data'            => [round($unpaid, 2), round(bcmul($paid, '-1'), 2)],
                    'backgroundColor' => [ChartColour::getColour(0), ChartColour::getColour(1)],
                ],

            ],
            'labels'   => [strval(trans('firefly.unpaid')), strval(trans('firefly.paid'))],

        ];

        return $data;
    }

    /**
     * @param Bill       $bill
     * @param Collection $entries
     *
     * @return array
     */
    public function single(Bill $bill, Collection $entries): array
    {
        $format       = (string)trans('config.month');
        $data         = ['count' => 3, 'labels' => [], 'datasets' => [],];
        $minAmount    = [];
        $maxAmount    = [];
        $actualAmount = [];
        /** @var Transaction $entry */
        foreach ($entries as $entry) {
            $data['labels'][] = $entry->date->formatLocalized($format);
            $minAmount[]      = round($bill->amount_min, 2);
            $maxAmount[]      = round($bill->amount_max, 2);
            // journalAmount has been collected in BillRepository::getJournals
            $actualAmount[] = bcmul($entry->transaction_amount, '-1');
        }

        $data['datasets'][] = [
            'type'  => 'bar',
            'label' => trans('firefly.minAmount'),
            'data'  => $minAmount,
        ];
        $data['datasets'][] = [
            'type'  => 'line',
            'label' => trans('firefly.billEntry'),
            'data'  => $actualAmount,
        ];
        $data['datasets'][] = [
            'type'  => 'bar',
            'label' => trans('firefly.maxAmount'),
            'data'  => $maxAmount,
        ];

        $data['count'] = count($data['datasets']);

        return $data;
    }
}
