<?php

namespace FireflyIII\Generator\Chart\PiggyBank;

use Carbon\Carbon;
use Config;
use Illuminate\Support\Collection;
use Preferences;


/**
 * Class ChartJsPiggyBankChartGenerator
 *
 * @package FireflyIII\Generator\Chart\PiggyBank
 */
class ChartJsPiggyBankChartGenerator implements PiggyBankChartGenerator
{

    /**
     * @param Collection $set
     *
     * @return array
     */
    public function history(Collection $set)
    {

        // language:
        $language = Preferences::get('language', 'en')->data;
        $format   = Config::get('firefly.monthAndDay.' . $language);

        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => 'Diff',
                    'data'  => []
                ]
            ],
        ];
        $sum  = '0';
        bcscale(2);
        foreach ($set as $entry) {
            $date                          = new Carbon($entry->date);
            $sum                           = bcadd($sum, $entry->sum);
            $data['labels'][]              = $date->formatLocalized($format);
            $data['datasets'][0]['data'][] = round($sum, 2);
        }

        return $data;
    }
}
