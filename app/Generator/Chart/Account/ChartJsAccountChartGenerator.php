<?php
/**
 * ChartJsAccountChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Generator\Chart\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Support\ChartColour;
use Illuminate\Support\Collection;

/**
 * Class ChartJsAccountChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Account
 */
class ChartJsAccountChartGenerator implements AccountChartGeneratorInterface
{

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function expenseAccounts(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $data = [
            'count'  => 1,
            'labels' => [], 'datasets' => [[
                                               'label' => trans('firefly.spent'),
                                               'data'  => []]]];
        foreach ($accounts as $account) {
            if ($account->difference > 0) {
                $data['labels'][]              = $account->name;
                $data['datasets'][0]['data'][] = $account->difference;
            }
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function frontpage(Collection $accounts, Carbon $start, Carbon $end): array
    {
        // language:
        $format  = (string)trans('config.month_and_day');
        $data    = ['count' => 0, 'labels' => [], 'datasets' => [],];
        $current = clone $start;
        while ($current <= $end) {
            $data['labels'][] = $current->formatLocalized($format);
            $current->addDay();
        }

        foreach ($accounts as $account) {
            $data['datasets'][] = [
                'label'                => $account->name,
                'fillColor'            => 'rgba(220,220,220,0.2)',
                'strokeColor'          => 'rgba(220,220,220,1)',
                'pointColor'           => 'rgba(220,220,220,1)',
                'pointStrokeColor'     => '#fff',
                'pointHighlightFill'   => '#fff',
                'pointHighlightStroke' => 'rgba(220,220,220,1)',
                'data'                 => $account->balances,
            ];
        }
        $data['count'] = count($data['datasets']);

        return $data;
    }

    /**
     * @param array $values
     * @param array $names
     *
     * @return array
     */
    public function pieChart(array $values, array $names): array
    {
        $data  = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];
        $index = 0;
        foreach ($values as $categoryId => $value) {

            // make larger than 0
            if (bccomp($value, '0') === -1) {
                $value = bcmul($value, '-1');
            }

            $data['datasets'][0]['data'][]            = round($value, 2);
            $data['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);
            $data['labels'][]                         = $names[$categoryId];
            $index++;
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function revenueAccounts(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $data = [
            'count'  => 1,
            'labels' => [], 'datasets' => [[
                                               'label' => trans('firefly.earned'),
                                               'data'  => []]]];
        foreach ($accounts as $account) {
            if ($account->difference > 0) {
                $data['labels'][]              = $account->name;
                $data['datasets'][0]['data'][] = $account->difference;
            }
        }

        return $data;
    }

    /**
     * @param Account $account
     * @param array   $labels
     * @param array   $dataSet
     *
     * @return array
     */
    public function single(Account $account, array $labels, array $dataSet): array
    {
        $data = [
            'count'    => 1,
            'labels'   => $labels,
            'datasets' => [
                [
                    'label' => $account->name,
                    'data'  => $dataSet,
                ],
            ],
        ];

        return $data;
    }
}
