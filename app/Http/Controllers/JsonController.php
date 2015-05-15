<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Preferences;
use Response;
use Session;
use Steam;

/**
 * Class JsonController
 *
 * @package FireflyIII\Http\Controllers
 */
class JsonController extends Controller
{


    /**
     * @param BillRepositoryInterface    $repository
     *
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxBillsPaid(BillRepositoryInterface $repository, AccountRepositoryInterface $accountRepository)
    {
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $amount = 0;

        // these two functions are the same as the chart TODO
        $bills = $repository->getActiveBills();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges = $repository->getRanges($bill, $start, $end);

            foreach ($ranges as $range) {
                // paid a bill in this range?
                $amount += $repository->getJournalsInRange($bill, $range['start'], $range['end'])->sum('amount');
            }
        }
        unset($ranges, $bill, $range, $bills);

        /**
         * Find credit card accounts and possibly unpaid credit card bills.
         */
        $creditCards = $accountRepository->getCreditCards();
        // if the balance is not zero, the monthly payment is still underway.
        /** @var Account $creditCard */
        foreach ($creditCards as $creditCard) {
            $balance = Steam::balance($creditCard, null, true);
            if ($balance == 0) {
                // find a transfer TO the credit card which should account for
                // anything paid. If not, the CC is not yet used.
                $amount += $accountRepository->getTransfersInRange($creditCard, $start, $end)->sum('amount');
            }
        }

        return Response::json(['box' => 'bills-paid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount]);
    }

    /**
     * @param BillRepositoryInterface    $repository
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxBillsUnpaid(BillRepositoryInterface $repository, AccountRepositoryInterface $accountRepository)
    {
        $amount = 0;
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $bills  = $repository->getActiveBills();
        $unpaid = new Collection; // bills

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges = $repository->getRanges($bill, $start, $end);

            foreach ($ranges as $range) {
                $journals = $repository->getJournalsInRange($bill, $range['start'], $range['end']);
                if ($journals->count() == 0) {
                    $unpaid->push([$bill, $range['start']]);
                }
            }
        }
        unset($bill, $bills, $range, $ranges);

        $creditCards = $accountRepository->getCreditCards();
        foreach ($creditCards as $creditCard) {
            $balance = Steam::balance($creditCard, null, true);
            $date    = new Carbon($creditCard->getMeta('ccMonthlyPaymentDate'));
            if ($balance < 0) {
                // unpaid! create a fake bill that matches the amount.
                $description = $creditCard->name;
                $fakeAmount  = $balance * -1;
                $fakeBill    = $repository->createFakeBill($description, $date, $fakeAmount);
                $unpaid->push([$fakeBill, $date]);
            }
        }
        /** @var Bill $entry */
        foreach ($unpaid as $entry) {
            $current = ($entry[0]->amount_max + $entry[0]->amount_min) / 2;
            $amount += $current;
        }

        return Response::json(['box' => 'bills-unpaid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount]);
    }

    /**
     * @param ReportQueryInterface $reportQuery
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxIn(ReportQueryInterface $reportQuery)
    {
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $amount = $reportQuery->incomeInPeriod($start, $end, true)->sum('queryAmount');

        return Response::json(['box' => 'in', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount]);
    }

    /**
     * @param ReportQueryInterface $reportQuery
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxOut(ReportQueryInterface $reportQuery)
    {
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $amount = $reportQuery->journalsByExpenseAccount($start, $end, true)->sum('queryAmount');

        return Response::json(['box' => 'out', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount]);
    }

    /**
     * Returns a list of categories.
     *
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(CategoryRepositoryInterface $repository)
    {
        $list   = $repository->getCategories();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts(AccountRepositoryInterface $accountRepository)
    {
        $list   = $accountRepository->getAccounts(['Expense account', 'Beneficiary account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts(AccountRepositoryInterface $accountRepository)
    {
        $list   = $accountRepository->getAccounts(['Revenue account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setSharedReports()
    {
        /** @var Preference $pref */
        $pref = Preferences::get('includeShared', false);
        $new  = !$pref->data;
        Preferences::set('includeShared', $new);


        return Response::json(['value' => $new]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSharedReports()
    {
        $pref = Preferences::get('includeShared', false);

        return Response::json(['value' => $pref->data]);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param TagRepositoryInterface $tagRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(TagRepositoryInterface $tagRepository)
    {
        $list   = $tagRepository->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->tag;
        }

        return Response::json($return);

    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactionJournals(JournalRepositoryInterface $repository, $what)
    {
        $descriptions = [];
        $dbType       = $repository->getTransactionType($what);

        $journals = $repository->getJournalsOfType($dbType);
        foreach ($journals as $j) {
            $descriptions[] = $j->description;
        }

        $descriptions = array_unique($descriptions);
        sort($descriptions);

        return Response::json($descriptions);


    }

}
