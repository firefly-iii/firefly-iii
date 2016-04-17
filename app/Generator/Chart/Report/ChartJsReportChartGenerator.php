<?php
declare(strict_types = 1);
namespace FireflyIII\Generator\Chart\Report;

use Illuminate\Support\Collection;

/**
 * Class ChartJsReportChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Report
 */
class ChartJsReportChartGenerator implements ReportChartGeneratorInterface
{

    /**
     * Same as above but other translations.
     *
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYearInOut(Collection $entries): array
    {
        $data = [
            'count'    => 2,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.income'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.expenses'),
                    'data'  => [],
                ],
            ],
        ];

        foreach ($entries as $entry) {
            $data['labels'][]              = $entry[0]->formatLocalized('%Y');
            $data['datasets'][0]['data'][] = round($entry[1], 2);
            $data['datasets'][1]['data'][] = round($entry[2], 2);
        }

        return $data;
    }

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function multiYearInOutSummarized(string $income, string $expense, int $count): array
    {
        $data                          = [
            'count'    => 2,
            'labels'   => [trans('firefly.sum_of_years'), trans('firefly.average_of_years')],
            'datasets' => [
                [
                    'label' => trans('firefly.income'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.expenses'),
                    'data'  => [],
                ],
            ],
        ];
        $data['datasets'][0]['data'][] = round($income, 2);
        $data['datasets'][1]['data'][] = round($expense, 2);
        $data['datasets'][0]['data'][] = round(($income / $count), 2);
        $data['datasets'][1]['data'][] = round(($expense / $count), 2);

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function netWorth(Collection $entries) : array
    {
        $format = (string)trans('config.month_and_day');
        $data   = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.net_worth'),
                    'data'  => [],
                ],
            ],
        ];
        foreach ($entries as $entry) {
            $data['labels'][]              = trim($entry['date']->formatLocalized($format));
            $data['datasets'][0]['data'][] = round($entry['net-worth'], 2);
        }

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function yearInOut(Collection $entries): array
    {
        // language:
        $format = (string)trans('config.month');

        $data = [
            'count'    => 2,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.income'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.expenses'),
                    'data'  => [],
                ],
            ],
        ];

        foreach ($entries as $entry) {
            $data['labels'][]              = $entry[0]->formatLocalized($format);
            $data['datasets'][0]['data'][] = round($entry[1], 2);
            $data['datasets'][1]['data'][] = round($entry[2], 2);
        }

        return $data;
    }

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function yearInOutSummarized(string $income, string $expense, int $count): array
    {

        $data                          = [
            'count'    => 2,
            'labels'   => [trans('firefly.sum_of_year'), trans('firefly.average_of_year')],
            'datasets' => [
                [
                    'label' => trans('firefly.income'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.expenses'),
                    'data'  => [],
                ],
            ],
        ];
        $data['datasets'][0]['data'][] = round($income, 2);
        $data['datasets'][1]['data'][] = round($expense, 2);
        $data['datasets'][0]['data'][] = round(($income / $count), 2);
        $data['datasets'][1]['data'][] = round(($expense / $count), 2);

        return $data;
    }
}
