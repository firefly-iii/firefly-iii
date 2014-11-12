<?php
use Carbon\Carbon;

/**
 * Class ReportController
 */
class ReportController extends BaseController
{


    /**
     *
     */
    public function index()
    {
        /** @var \FireflyIII\Database\TransactionJournal $journals */
        $journals = App::make('FireflyIII\Database\TransactionJournal');
        $journal  = $journals->first();

        $date  = $journal->date;
        $years = [];
        while ($date <= Carbon::now()) {
            $years[] = $date->format('Y');
            $date->addYear();
        }


        return View::make('reports.index', compact('years'))->with('title', 'Reports')->with('mainTitleIcon', 'fa-line-chart');
    }

    /**
     * @param $year
     */
    public function year($year)
    {
        try {
            $date = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }

        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = App::make('FireflyIII\Database\TransactionJournal');

        // get some sums going
        $summary = [];


        $end = clone $date;
        $end->endOfYear();
        while ($date < $end) {
            $summary[] = ['month' => $date->format('F'), 'income' => $tj->getSumOfIncomesByMonth($date), 'expense' => $tj->getSumOfExpensesByMonth($date),];
            $date->addMonth();
        }


        // draw some charts etc.
        return View::make('reports.year', compact('summary'))->with('title', 'Reports')->with('mainTitleIcon', 'fa-line-chart')->with('subTitle', $year)->with(
            'subTitleIcon', 'fa-bar-chart'
        )->with('year', $year);
    }

}