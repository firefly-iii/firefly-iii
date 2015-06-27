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
                    'label' => 'Spent',
                    'data'  => []
                ]
            ],
        ];
        foreach ($entries as $entry) {
            if ($entry['sum'] != 0) {
                $data['labels'][]              = $entry['name'];
                $data['datasets'][0]['data'][] = round($entry['sum'],2);
            }
        }

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function month(Collection $entries)
    {
    }

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $categories, Collection $entries)
    {
    }
}