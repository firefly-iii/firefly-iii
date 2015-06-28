<?php

namespace FireflyIII\Generator\Chart\Category;

use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;

/**
 * Class GoogleCategoryChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Category
 */
class GoogleCategoryChartGenerator implements CategoryChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function all(Collection $entries)
    {
        $chart = new GChart;
        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        /** @var array $entry */
        foreach ($entries as $entry) {
            $chart->addRow($entry[0], $entry[1]);
        }

        $chart->generate();

        return $chart->getData();
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries)
    {
        $chart = new GChart;
        $chart->addColumn(trans('firefly.category'), 'string');
        $chart->addColumn(trans('firefly.spent'), 'number');


        /** @var array $entry */
        foreach ($entries as $entry) {
            $sum = $entry['sum'];
            if ($sum != 0) {
                $chart->addRow($entry['name'], $sum);
            }
        }

        $chart->generate();

        return $chart->getData();
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function month(Collection $entries)
    {
        $chart = new GChart;

        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        /** @var array $entry */
        foreach ($entries as $entry) {
            $chart->addRow($entry[0], $entry[1]);
        }
        $chart->generate();

        return $chart->getData();
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $categories, Collection $entries)
    {
        $chart = new GChart;

        $chart->addColumn(trans('firefly.month'), 'date');
        foreach ($categories as $category) {
            $chart->addColumn($category->name, 'number');
        }
        /** @var array $entry */
        foreach ($entries as $entry) {
            $chart->addRowArray($entry);
        }
        $chart->generate();
        return $chart->getData();

    }
}
