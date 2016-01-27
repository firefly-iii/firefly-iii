<?php

namespace FireflyIII\Generator\Chart\Category;

use Illuminate\Support\Collection;


/**
 * Class ChartJsCategoryChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Category
 */
class ChartJsCategoryChartGenerator implements CategoryChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function all(Collection $entries)
    {


        $data = [
            'count'    => 2,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.spent'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.earned'),
                    'data'  => [],
                ],
            ],
        ];

        foreach ($entries as $entry) {
            $data['labels'][] = $entry[1];
            $spent            = round($entry[2], 2);
            $earned           = round($entry[3], 2);

            $data['datasets'][0]['data'][] = $spent == 0 ? null : $spent * -1;
            $data['datasets'][1]['data'][] = $earned == 0 ? null : $earned;
        }

        return $data;
    }

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function earnedInPeriod(Collection $categories, Collection $entries)
    {

        // language:
        $format = (string)trans('config.month');

        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];

        foreach ($categories as $category) {
            $data['labels'][] = $category->name;
        }

        foreach ($entries as $entry) {
            $date = $entry[0]->formatLocalized($format);
            array_shift($entry);
            $data['count']++;
            $data['datasets'][] = ['label' => $date, 'data' => $entry];
        }

        return $data;

    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries)
    {
        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.spent'),
                    'data'  => [],
                ],
            ],
        ];
        foreach ($entries as $entry) {
            if ($entry->spent != 0) {
                $data['labels'][]              = $entry->name;
                $data['datasets'][0]['data'][] = round(($entry->spent * -1), 2);
            }
        }

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries)
    {
        // dataset:
        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];
        // get labels from one of the categories (assuming there's at least one):
        $first = $entries->first();
        $keys  = array_keys($first['spent']);
        foreach ($keys as $year) {
            $data['labels'][] = strval($year);
        }

        // then, loop all entries and create datasets:
        foreach ($entries as $entry) {
            $name   = $entry['name'];
            $spent  = $entry['spent'];
            $earned = $entry['earned'];
            if (array_sum(array_values($spent)) != 0) {
                $data['datasets'][] = ['label' => 'Spent in category ' . $name, 'data' => array_values($spent)];
            }
            if (array_sum(array_values($earned)) != 0) {
                $data['datasets'][] = ['label' => 'Earned in category ' . $name, 'data' => array_values($earned)];
            }
        }
        $data['count'] = count($data['datasets']);

        return $data;

    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $entries
     *
     * @return array
     */
    public function period(Collection $entries)
    {
        return $this->all($entries);

    }

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function spentInPeriod(Collection $categories, Collection $entries)
    {

        // language:
        $format = (string)trans('config.month');

        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];

        foreach ($categories as $category) {
            $data['labels'][] = $category->name;
        }

        foreach ($entries as $entry) {
            $date = $entry[0]->formatLocalized($format);
            array_shift($entry);
            $data['count']++;
            $data['datasets'][] = ['label' => $date, 'data' => $entry];
        }

        return $data;

    }
}
