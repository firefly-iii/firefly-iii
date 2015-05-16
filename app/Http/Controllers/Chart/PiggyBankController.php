<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 16/05/15
 * Time: 09:36
 */

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;
use Response;


/**
 * Class PiggyBankController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class PiggyBankController extends Controller
{
    /**
     * Shows the piggy bank history.
     *
     * @param GChart                       $chart
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function piggyBankHistory(GChart $chart, PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $chart->addColumn(trans('firefly.date'), 'date');
        $chart->addColumn(trans('firefly.balance'), 'number');

        /** @var Collection $set */
        $set = $repository->getEventSummarySet($piggyBank);
        $sum = 0;

        foreach ($set as $entry) {
            $sum += floatval($entry->sum);
            $chart->addRow(new Carbon($entry->date), $sum);
        }

        $chart->generate();

        return Response::json($chart->getData());

    }
}