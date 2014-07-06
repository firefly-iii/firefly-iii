<?php

use Carbon\Carbon as Carbon;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class ChartController extends BaseController
{

    public function __construct(ARI $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * Show home charts.
     */
    public function home(Account $account = null)
    {
        // chart
        $chart = App::make('gchart');
        $chart->addColumn('Day of the month', 'date');

        // date
        $today = new Carbon;
        $past = clone $today;
        $past->subMonth();
        $current = clone $past;

        if (is_null($account)) {
            // get accounts:
            $accounts = $this->accounts->getActiveDefault();

            foreach ($accounts as $account) {
                $chart->addColumn($account->name, 'number');
            }

            while ($current <= $today) {
                $row = [clone $current];

                // loop accounts:
                foreach ($accounts as $account) {
                    $row[] = $account->balance(clone $current);
                }
                $current->addDay();
                $chart->addRowArray($row);
            }
        } else {
            $chart->addColumn($account->name, 'number');
            while ($current <= $today) {
                $row = [clone $current,$account->balance(clone $current)];
                $current->addDay();
                $chart->addRowArray($row);
            }
        }

        $chart->generate();
        return $chart->getData();


    }
} 