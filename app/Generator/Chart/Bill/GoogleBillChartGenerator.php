<?php

namespace FireflyIII\Generator\Chart\Bill;

use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;

/**
 * Class GoogleBillChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Bill
 */
class GoogleBillChartGenerator implements BillChartGenerator
{


    /**
     * @param Collection $paid
     * @param Collection $unpaid
     *
     * @return array
     */
    public function frontpage(Collection $paid, Collection $unpaid)
    {
        // loop paid and create single entry:
        $paidDescriptions   = [];
        $paidAmount         = 0;
        $unpaidDescriptions = [];
        $unpaidAmount       = 0;


        /** @var TransactionJournal $entry */
        foreach ($paid as $entry) {

            $paidDescriptions[] = $entry->description;
            $paidAmount += floatval($entry->amount);
        }

        // loop unpaid:
        /** @var Bill $entry */
        foreach ($unpaid as $entry) {
            $description          = $entry[0]->name . ' (' . $entry[1]->format('jS M Y') . ')';
            $amount               = ($entry[0]->amount_max + $entry[0]->amount_min) / 2;
            $unpaidDescriptions[] = $description;
            $unpaidAmount += $amount;
            unset($amount, $description);
        }

        $chart = new GChart;
        $chart->addColumn(trans('firefly.name'), 'string');
        $chart->addColumn(trans('firefly.amount'), 'number');

        $chart->addRow(trans('firefly.unpaid') . ': ' . join(', ', $unpaidDescriptions), $unpaidAmount);
        $chart->addRow(trans('firefly.paid') . ': ' . join(', ', $paidDescriptions), $paidAmount);

        $chart->generate();

        return $chart->getData();
    }

    /**
     * @param Bill       $bill
     * @param Collection $entries
     *
     * @return mixed
     */
    public function single(Bill $bill, Collection $entries)
    {
        // make chart:
        $chart = new GChart;
        $chart->addColumn(trans('firefly.date'), 'date');
        $chart->addColumn(trans('firefly.maxAmount'), 'number');
        $chart->addColumn(trans('firefly.minAmount'), 'number');
        $chart->addColumn(trans('firefly.billEntry'), 'number');

        /** @var TransactionJournal $result */
        foreach ($entries as $result) {
            $chart->addRow(clone $result->date, floatval($bill->amount_max), floatval($bill->amount_min), floatval($result->amount));
        }

        $chart->generate();

        return $chart->getData();
    }
}
