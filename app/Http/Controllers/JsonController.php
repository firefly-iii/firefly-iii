<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Input;
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
     *
     */
    public function box(BillRepositoryInterface $repository)
    {
        $amount = 0;
        $start  = Session::get('start');
        $end    = Session::get('end');
        $box    = 'empty';
        switch (Input::get('box')) {
            case 'in':
                $box = Input::get('box');
                $in  = Auth::user()->transactionjournals()
                           ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                           ->before($end)
                           ->after($start)
                           ->transactionTypes(['Deposit'])
                           ->where('transactions.amount', '>', 0)
                           ->first([DB::Raw('SUM(transactions.amount) as `amount`')]);
                if (!is_null($in)) {
                    $amount = floatval($in->amount);
                }

                break;
            case 'out':
                $box = Input::get('box');
                $in  = Auth::user()->transactionjournals()
                           ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                           ->before($end)
                           ->after($start)
                           ->transactionTypes(['Withdrawal'])
                           ->where('transactions.amount', '>', 0)
                           ->first([DB::Raw('SUM(transactions.amount) as `amount`')]);
                if (!is_null($in)) {
                    $amount = floatval($in->amount);
                }

                break;
            case 'bills-unpaid':
                $box   = 'bills-unpaid';
                $bills = Auth::user()->bills()->where('active', 1)->get();

                /** @var Bill $bill */
                foreach ($bills as $bill) {
                    $ranges = $repository->getRanges($bill, $start, $end);

                    foreach ($ranges as $range) {
                        // paid a bill in this range?
                        $count = $bill->transactionjournals()->before($range['end'])->after($range['start'])->count();
                        if ($count == 0) {
                            $amount += floatval($bill->amount_max + $bill->amount_min / 2);
                        }

                    }
                }

                /**
                 * Find credit card accounts and possibly unpaid credit card bills.
                 */
                $creditCards = Auth::user()->accounts()
                                   ->hasMetaValue('accountRole', 'ccAsset')
                                   ->hasMetaValue('ccType', 'monthlyFull')
                                   ->get(
                                       [
                                           'accounts.*',
                                           'ccType.data as ccType',
                                           'accountRole.data as accountRole'
                                       ]
                                   );
                // if the balance is not zero, the monthly payment is still underway.
                /** @var Account $creditCard */
                foreach ($creditCards as $creditCard) {
                    $balance = Steam::balance($creditCard, null, true);
                    if ($balance < 0) {
                        // unpaid!
                        $amount += $balance * -1;
                    }
                }

                break;
            case 'bills-paid':
                $box = 'bills-paid';
                // these two functions are the same as the chart TODO
                $bills = Auth::user()->bills()->where('active', 1)->get();

                /** @var Bill $bill */
                foreach ($bills as $bill) {
                    $ranges = $repository->getRanges($bill, $start, $end);

                    foreach ($ranges as $range) {
                        // paid a bill in this range?
                        $count = $bill->transactionjournals()->before($range['end'])->after($range['start'])->count();
                        if ($count != 0) {
                            $journal       = $bill->transactionjournals()->with('transactions')->before($range['end'])->after($range['start'])->first();
                            $currentAmount = 0;
                            foreach ($journal->transactions as $t) {
                                if (floatval($t->amount) > 0) {
                                    $currentAmount = floatval($t->amount);
                                }
                            }
                            $amount += $currentAmount;
                        }

                    }
                }

                /**
                 * Find credit card accounts and possibly unpaid credit card bills.
                 */
                $creditCards = Auth::user()->accounts()
                                   ->hasMetaValue('accountRole', 'ccAsset')
                                   ->hasMetaValue('ccType', 'monthlyFull')
                                   ->get(
                                       [
                                           'accounts.*',
                                           'ccType.data as ccType',
                                           'accountRole.data as accountRole'
                                       ]
                                   );
                // if the balance is not zero, the monthly payment is still underway.
                /** @var Account $creditCard */
                foreach ($creditCards as $creditCard) {
                    $balance = Steam::balance($creditCard, null, true);
                    if ($balance == 0) {
                        // find a transfer TO the credit card which should account for
                        // anything paid. If not, the CC is not yet used.
                        $transactions = $creditCard->transactions()
                                                   ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                                   ->before($end)->after($start)->get();
                        if ($transactions->count() > 0) {
                            /** @var Transaction $transaction */
                            foreach ($transactions as $transaction) {
                                $journal = $transaction->transactionJournal;
                                if ($journal->transactionType->type == 'Transfer') {
                                    $amount += floatval($transaction->amount);
                                }
                            }
                        }
                    }
                }
        }

        return Response::json(['box' => $box, 'amount' => Amount::format($amount, false), 'amount_raw' => $amount]);
    }

    /**
     * Returns a list of categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $list   = Auth::user()->categories()->orderBy('name', 'ASC')->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts()
    {
        $list   = Auth::user()->accounts()->orderBy('accounts.name', 'ASC')->accountTypeIn(['Expense account', 'Beneficiary account'])->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts()
    {
        $list   = Auth::user()->accounts()->accountTypeIn(['Revenue account'])->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
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
        $pref = Preferences::get('showSharedReports', false);
        $new  = !$pref->data;
        Preferences::set('showSharedReports', $new);


        return Response::json(['value' => $new]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSharedReports()
    {
        $pref = Preferences::get('showSharedReports', false);

        return Response::json(['value' => $pref->data]);
    }

    /**
     * @param $what
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactionJournals($what)
    {
        $descriptions = [];
        $dbType       = TransactionType::whereType($what)->first();
        $journals     = Auth::user()->transactionjournals()->where('transaction_type_id', $dbType->id)
                            ->orderBy('id', 'DESC')->take(50)
                            ->get();
        foreach ($journals as $j) {
            $descriptions[] = $j->description;
        }

        $descriptions = array_unique($descriptions);
        sort($descriptions);

        return Response::json($descriptions);


    }

}
