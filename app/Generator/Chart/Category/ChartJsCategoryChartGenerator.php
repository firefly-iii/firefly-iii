<?php

namespace FireflyIII\Generator\Chart\Category;

use Config;
use Illuminate\Support\Collection;
use Preferences;


/**
 * Class ChartJsCategoryChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Category
 */
class ChartJsCategoryChartGenerator implements CategoryChartGenerator
{

    /**
     * @param Collection $entries
     * @param string $dateFormat
     *
     * @return array
     */
    public function all(Collection $entries, $dateFormat = 'month')
    {

        // language:
        $language = Preferences::get('language', 'en')->data;
        $format   = Config::get('firefly.' . $dateFormat . '.' . $language);

        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.spent'),
                    'data'  => []
                ]
            ],
        ];

        foreach ($entries as $entry) {
            $data['labels'][]              = $entry[0]->formatLocalized($format);
            $data['datasets'][0]['data'][] = round($entry[1], 2);
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
                    'data'  => []
                ]
            ],
        ];
        foreach ($entries as $entry) {
            if ($entry['sum'] != 0) {
                $data['labels'][]              = $entry['name'];
                $data['datasets'][0]['data'][] = round($entry['sum'], 2);
            }
        }

        return $data;
    }

    /**
     * @codeCoverageIgnore
     * @param Collection $entries
     *
     * @return array
     */
    public function month(Collection $entries)
    {
        return $this->all($entries, 'monthAndDay');

    }

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $categories, Collection $entries)
    {

        // language:
        $language = Preferences::get('language', 'en')->data;
        $format   = Config::get('firefly.month.' . $language);

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
