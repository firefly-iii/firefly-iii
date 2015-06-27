<?php

namespace FireflyIII\Generator\Chart\PiggyBank;

use Carbon\Carbon;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;


/**
 * Class GooglePiggyBankChartGenerator
 *
 * @package FireflyIII\Generator\Chart\PiggyBank
 */
class GooglePiggyBankChartGenerator implements PiggyBankChartGenerator
{

    /**
     * @param Collection $set
     *
     * @return array
     */
    public function history(Collection $set)
    {
        $chart = new GChart;
        $chart->addColumn(trans('firefly.date'), 'date');
        $chart->addColumn(trans('firefly.balance'), 'number');

        $sum = '0';
        bcscale(2);

        foreach ($set as $entry) {
            $sum = bcadd($sum, $entry->sum);
            $chart->addRow(new Carbon($entry->date), $sum);
        }

        $chart->generate();

        return $chart->getData();
    }
}