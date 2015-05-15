<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use Session;
use Steam;

/**
 * Class GoogleChartController
 *
 * @package FireflyIII\Http\Controllers
 */
class GoogleChartController extends Controller
{


    /**
     * @param GChart  $chart
     * @param Account $account
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accountBalanceChart(GChart $chart, Account $account)
    {
        $chart->addColumn(trans('firefly.dayOfMonth'), 'date');
        $chart->addColumn(trans('firefly.balanceFor', ['name' => $account->name]), 'number');
        $chart->addCertainty(1);

        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());
        $current = clone $start;
        $today   = new Carbon;

        while ($end >= $current) {
            $certain = $current < $today;
            $chart->addRow(clone $current, Steam::balance($account, $current), $certain);
            $current->addDay();
        }


        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param GChart                     $chart
     * @param AccountRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allAccountsBalanceChart(GChart $chart, AccountRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.dayOfMonth'), 'date');

        $frontPage = Preferences::get('frontPageAccounts', []);
        $start     = Session::get('start', Carbon::now()->startOfMonth());
        $end       = Session::get('end', Carbon::now()->endOfMonth());
        $accounts  = $repository->getFrontpageAccounts($frontPage);

        $index = 1;
        /** @var Account $account */
        foreach ($accounts as $account) {
            $chart->addColumn(trans('firefly.balanceFor', ['name' => $account->name]), 'number');
            $chart->addCertainty($index);
            $index++;
        }
        $current = clone $start;
        $current->subDay();
        $today = Carbon::now();
        while ($end >= $current) {
            $row     = [clone $current];
            $certain = $current < $today;
            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
                $row[] = $certain;
            }
            $chart->addRowArray($row);
            $current->addDay();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param                           $year
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allBudgetsAndSpending(GChart $chart, BudgetRepositoryInterface $repository, $year, $shared = false)
    {
        $budgets = $repository->getBudgets();
        $chart->addColumn(trans('firefly.month'), 'date');
        foreach ($budgets as $budget) {
            $chart->addColumn($budget->name, 'number');
        }

        $start = Carbon::createFromDate(intval($year), 1, 1);
        $end   = clone $start;
        $end->endOfYear();

        while ($start <= $end) {
            $row = [clone $start];
            foreach ($budgets as $budget) {
                $spent = $repository->spentInMonth($budget, $start);
                $row[] = $spent;
            }
            $chart->addRowArray($row);
            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allBudgetsHomeChart(GChart $chart, BudgetRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.budget'), 'string');
        $chart->addColumn(trans('firefly.left'), 'number');
        $chart->addColumn(trans('firefly.spent'), 'number');
        $chart->addColumn(trans('firefly.overspent'), 'number');

        $budgets    = $repository->getBudgets();
        $start      = Session::get('start', Carbon::now()->startOfMonth());
        $end        = Session::get('end', Carbon::now()->endOfMonth());
        $allEntries = new Collection;

        foreach ($budgets as $budget) {
            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);
            if ($repetitions->count() == 0) {
                $expenses = $repository->sumBudgetExpensesInPeriod($budget, $start, $end);
                $allEntries->push([$budget->name, 0, 0, $expenses]);
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $expenses  = $repository->sumBudgetExpensesInPeriod($budget, $repetition->startdate, $repetition->enddate);
                $left      = $expenses < floatval($repetition->amount) ? floatval($repetition->amount) - $expenses : 0;
                $spent     = $expenses > floatval($repetition->amount) ? 0 : $expenses;
                $overspent = $expenses > floatval($repetition->amount) ? $expenses - floatval($repetition->amount) : 0;
                $allEntries->push(
                    [$budget->name . ' (' . $repetition->startdate->formatLocalized($this->monthAndDayFormat) . ')',
                     $left,
                     $spent,
                     $overspent
                    ]
                );
            }
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($start, $end);
        $allEntries->push([trans('firefly.noBudget'), 0, 0, $noBudgetExpenses]);

        foreach ($allEntries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $chart->addRow($entry[0], $entry[1], $entry[2], $entry[3]);
            }
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allCategoriesHomeChart(GChart $chart, CategoryRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.category'), 'string');
        $chart->addColumn(trans('firefly.spent'), 'number');

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());
        $set   = $repository->getCategoriesAndExpenses($start, $end);

        foreach ($set as $entry) {
            $isEncrypted = intval($entry->encrypted) == 1 ? true : false;
            $name        = strlen($entry->name) == 0 ? trans('firefly.noCategory') : $entry->name;
            $name        = $isEncrypted ? Crypt::decrypt($name) : $name;
            $chart->addRow($name, floatval($entry->sum));
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                  $chart
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function billOverview(GChart $chart, BillRepositoryInterface $repository, Bill $bill)
    {

        $chart->addColumn(trans('firefly.date'), 'date');
        $chart->addColumn(trans('firefly.maxAmount'), 'number');
        $chart->addColumn(trans('firefly.minAmount'), 'number');
        $chart->addColumn(trans('firefly.billEntry'), 'number');

        // get first transaction or today for start:
        $results = $repository->getJournals($bill);
        /** @var TransactionJournal $result */
        foreach ($results as $result) {
            $chart->addRow(clone $result->date, floatval($bill->amount_max), floatval($bill->amount_min), floatval($result->amount));
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                     $chart
     *
     * @param BillRepositoryInterface    $repository
     * @param AccountRepositoryInterface $accounts
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function billsOverview(GChart $chart, BillRepositoryInterface $repository, AccountRepositoryInterface $accounts)
    {
        $chart->addColumn(trans('firefly.name'), 'string');
        $chart->addColumn(trans('firefly.amount'), 'number');

        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $bills  = $repository->getActiveBills();
        $paid   = new Collection; // journals.
        $unpaid = new Collection; // bills
        // loop paid and create single entry:
        $paidDescriptions   = [];
        $paidAmount         = 0;
        $unpaidDescriptions = [];
        $unpaidAmount       = 0;

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges = $repository->getRanges($bill, $start, $end);

            foreach ($ranges as $range) {
                // paid a bill in this range?
                $journals = $repository->getJournalsInRange($bill, $range['start'], $range['end']);
                if ($journals->count() == 0) {
                    $unpaid->push([$bill, $range['start']]);
                } else {
                    $paid = $paid->merge($journals);
                }

            }
        }

        $creditCards = $accounts->getCreditCards();
        foreach ($creditCards as $creditCard) {
            $balance = Steam::balance($creditCard, null, true);
            $date    = new Carbon($creditCard->getMeta('ccMonthlyPaymentDate'));
            if ($balance < 0) {
                // unpaid! create a fake bill that matches the amount.
                $description = $creditCard->name;
                $amount      = $balance * -1;
                $fakeBill    = $repository->createFakeBill($description, $date, $amount);
                unset($description, $amount);
                $unpaid->push([$fakeBill, $date]);
            }
            if ($balance == 0) {
                // find transfer(s) TO the credit card which should account for
                // anything paid. If not, the CC is not yet used.
                $journals = $accounts->getTransfersInRange($creditCard, $start, $end);
                $paid     = $paid->merge($journals);
            }
        }


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

        $chart->addRow(trans('firefly.unpaid') . ': ' . join(', ', $unpaidDescriptions), $unpaidAmount);
        $chart->addRow(trans('firefly.paid') . ': ' . join(', ', $paidDescriptions), $paidAmount);
        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param LimitRepetition           $repetition
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetLimitSpending(GChart $chart, BudgetRepositoryInterface $repository, Budget $budget, LimitRepetition $repetition)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;

        $chart->addColumn(trans('firefly.day'), 'date');
        $chart->addColumn(trans('firefly.left'), 'number');


        $amount = $repetition->amount;

        while ($start <= $end) {
            /*
             * Sum of expenses on this day:
             */
            $sum = $repository->expensesOnDay($budget, $start);
            $amount += $sum;
            $chart->addRow(clone $start, $amount);
            $start->addDay();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param int                       $year
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetsAndSpending(GChart $chart, BudgetRepositoryInterface $repository, Budget $budget, $year = 0)
    {
        $chart->addColumn(trans('firefly.month'), 'date');
        $chart->addColumn(trans('firefly.budgeted'), 'number');
        $chart->addColumn(trans('firefly.spent'), 'number');

        if ($year == 0) {
            $start = $repository->getFirstBudgetLimitDate($budget);
            $end   = $repository->getLastBudgetLimitDate($budget);
        } else {
            $start = Carbon::createFromDate(intval($year), 1, 1);
            $end   = clone $start;
            $end->endOfYear();
        }

        while ($start <= $end) {
            $spent    = $repository->spentInMonth($budget, $start);
            $budgeted = $repository->getLimitAmountOnDate($budget, $start);
            $chart->addRow(clone $start, $budgeted, $spent);
            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoryOverviewChart(GChart $chart, CategoryRepositoryInterface $repository, Category $category)
    {
        // oldest transaction in category:
        $start = $repository->getFirstActivityDate($category);

        /** @var Preference $range */
        $range = Preferences::get('viewRange', '1M');
        // jump to start of week / month / year / etc (TODO).
        $start = Navigation::startOfPeriod($start, $range->data);

        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        $end = new Carbon;
        while ($start <= $end) {

            $currentEnd = Navigation::endOfPeriod($start, $range->data);
            $spent      = $repository->spentInPeriodSum($category, $start, $currentEnd);
            $chart->addRow(clone $start, $spent);

            $start = Navigation::addPeriod($start, $range->data, 0);
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoryPeriodChart(GChart $chart, CategoryRepositoryInterface $repository, Category $category)
    {
        $start = clone Session::get('start', Carbon::now()->startOfMonth());
        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        $end = Session::get('end', Carbon::now()->endOfMonth());
        while ($start <= $end) {
            $spent = $repository->spentOnDaySum($category, $start);
            $chart->addRow(clone $start, $spent);
            $start->addDay();
        }

        $chart->generate();

        return Response::json($chart->getData());


    }


    /**
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

    /**
     * @param GChart               $chart
     * @param ReportQueryInterface $query
     * @param                      $year
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInExp(GChart $chart, ReportQueryInterface $query, $year, $shared = false)
    {
        $start = new Carbon('01-01-' . $year);
        $chart->addColumn(trans('firefly.month'), 'date');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');

        if ($shared == 'shared') {
            $shared = true;
        }

        // get report query interface.

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {
            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            // total income && total expenses:
            $incomeSum  = floatval($query->incomeInPeriod($start, $currentEnd, $shared)->sum('queryAmount'));
            $expenseSum = floatval($query->journalsByExpenseAccount($start, $currentEnd, $shared)->sum('queryAmount'));

            $chart->addRow(clone $start, $incomeSum, $expenseSum);
            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart               $chart
     * @param ReportQueryInterface $query
     * @param                      $year
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInExpSum(GChart $chart, ReportQueryInterface $query, $year, $shared = false)
    {
        $start = new Carbon('01-01-' . $year);
        $chart->addColumn(trans('firefly.summary'), 'string');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');

        if ($shared == 'shared') {
            $shared = true;
        }

        $income  = 0;
        $expense = 0;
        $count   = 0;

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {
            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            // total income:
            $incomeSum = floatval($query->incomeInPeriod($start, $currentEnd, $shared)->sum('queryAmount'));
            // total expenses:
            $expenseSum = floatval($query->journalsByExpenseAccount($start, $currentEnd, $shared)->sum('queryAmount'));

            $income += $incomeSum;
            $expense += $expenseSum;
            $count++;
            $start->addMonth();
        }


        $chart->addRow(trans('firefly.sum'), $income, $expense);
        $count = $count > 0 ? $count : 1;
        $chart->addRow(trans('firefly.average'), ($income / $count), ($expense / $count));

        $chart->generate();

        return Response::json($chart->getData());

    }


}
