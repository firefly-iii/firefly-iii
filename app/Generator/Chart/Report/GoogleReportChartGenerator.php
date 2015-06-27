<?php

namespace FireflyIII\Generator\Chart\Report;

use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;

/**
 * Class GoogleReportChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Report
 */
class GoogleReportChartGenerator implements ReportChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function yearInOut(Collection $entries)
    {
        $chart = new GChart;
        $chart->addColumn(trans('firefly.month'), 'date');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');

        /** @var array $entry */
        foreach ($entries as $entry) {
            $chart->addRowArray($entry);
        }
        $chart->generate();

        return $chart->getData();
    }

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function yearInOutSummarized($income, $expense, $count)
    {
        $chart = new GChart;

        $chart->addColumn(trans('firefly.summary'), 'string');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');
        $chart->addRow(trans('firefly.sum'), $income, $expense);
        $chart->addRow(trans('firefly.average'), ($income / $count), ($expense / $count));

        $chart->generate();

        return $chart->getData();
    }
}