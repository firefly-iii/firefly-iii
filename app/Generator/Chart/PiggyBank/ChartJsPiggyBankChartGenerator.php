<?php
/**
 * ChartJsPiggyBankChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Generator\Chart\PiggyBank;

use Carbon\Carbon;
use Illuminate\Support\Collection;


/**
 * Class ChartJsPiggyBankChartGenerator
 *
 * @package FireflyIII\Generator\Chart\PiggyBank
 */
class ChartJsPiggyBankChartGenerator implements PiggyBankChartGeneratorInterface
{

    /**
     * @param Collection $set
     *
     * @return array
     */
    public function history(Collection $set): array
    {

        // language:
        $format = (string)trans('config.month_and_day');

        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => 'Diff',
                    'data'  => [],
                ],
            ],
        ];
        $sum  = '0';
        foreach ($set as $key => $value) {
            $date                          = new Carbon($key);
            $sum                           = bcadd($sum, $value);
            $data['labels'][]              = $date->formatLocalized($format);
            $data['datasets'][0]['data'][] = round($sum, 2);
        }

        return $data;
    }
}
