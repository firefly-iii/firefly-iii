<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use Carbon\Carbon;
use Config;
use ExpandedForm;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Navigation;
use Redirect;
use Session;
use Steam;
use View;

/**
 * Class PiggyBankController
 *
 * @package FireflyIII\Http\Controllers
 */
class PiggyBankController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Piggy banks');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');
    }

    /**
     * Add money to piggy bank
     *
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function add(PiggyBank $piggyBank, AccountRepositoryInterface $repository)
    {
        $leftOnAccount = $repository->leftOnAccount($piggyBank->account);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggyBank->targetamount - $savedSoFar;
        $maxAmount     = min($leftOnAccount, $leftToSave);


        \Log::debug('Now going to view for piggy bank #' . $piggyBank->id . ' (' . $piggyBank->name . ')');

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount'));
    }

    /**
     * @return mixed
     */
    public function create()
    {

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = ExpandedForm::makeSelectList(Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']));
        $subTitle     = 'Create new piggy bank';
        $subTitleIcon = 'fa-plus';

        return view('piggy-banks.create', compact('accounts', 'periods', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = 'Delete "' . e($piggyBank->name) . '"';

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBank $piggyBank)
    {

        Session::flash('success', 'Piggy bank "' . e($piggyBank->name) . '" deleted.');
        $piggyBank->delete();

        return Redirect::route('piggy-banks.index');
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function edit(PiggyBank $piggyBank)
    {

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = ExpandedForm::makeSelectList(Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']));
        $subTitle     = 'Edit piggy bank "' . e($piggyBank->name) . '"';
        $subTitleIcon = 'fa-pencil';

        /*
         * Flash some data to fill the form.
         */
        if (is_null($piggyBank->targetdate) || $piggyBank->targetdate == '') {
            $targetDate = null;
        } else {
            $targetDate = new Carbon($piggyBank->targetdate);
            $targetDate = $targetDate->format('Y-m-d');
        }
        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
                      'reminder'     => $piggyBank->reminder,
                      'remind_me'    => intval($piggyBank->remind_me) == 1 || !is_null($piggyBank->reminder) ? true : false
        ];
        Session::flash('preFilled', $preFilled);

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'accounts', 'periods', 'preFilled'));
    }


    /**
     * @return $this
     */
    public function index(AccountRepositoryInterface $repository)
    {
        /** @var Collection $piggyBanks */
        $piggyBanks = Auth::user()->piggyBanks()->where('repeats', 0)->get();

        $accounts = [];
        /** @var PiggyBank $piggyBank */
        foreach ($piggyBanks as $piggyBank) {
            $piggyBank->savedSoFar = floatval($piggyBank->currentRelevantRep()->currentamount);
            $piggyBank->percentage = intval($piggyBank->savedSoFar / $piggyBank->targetamount * 100);
            $piggyBank->leftToSave = $piggyBank->targetamount - $piggyBank->savedSoFar;

            /*
             * Fill account information:
             */
            $account = $piggyBank->account;
            if (!isset($accounts[$account->id])) {
                $accounts[$account->id] = [
                    'name'              => $account->name,
                    'balance'           => Steam::balance($account),
                    'leftForPiggyBanks' => $repository->leftOnAccount($account),
                    'sumOfSaved'        => $piggyBank->savedSoFar,
                    'sumOfTargets'      => floatval($piggyBank->targetamount),
                    'leftToSave'        => $piggyBank->leftToSave
                ];
            } else {
                $accounts[$account->id]['sumOfSaved'] += $piggyBank->savedSoFar;
                $accounts[$account->id]['sumOfTargets'] += floatval($piggyBank->targetamount);
                $accounts[$account->id]['leftToSave'] += $piggyBank->leftToSave;
            }
        }

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }


    /**
     * POST add money to piggy bank
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(PiggyBank $piggyBank, PiggyBankRepositoryInterface $repository, AccountRepositoryInterface $accounts)
    {
        $amount        = round(floatval(Input::get('amount')), 2);
        $leftOnAccount = $accounts->leftOnAccount($piggyBank->account);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggyBank->targetamount - $savedSoFar;
        $maxAmount     = round(min($leftOnAccount, $leftToSave), 2);

        if ($amount <= $maxAmount) {
            $repetition = $piggyBank->currentRelevantRep();
            $repetition->currentamount += $amount;
            $repetition->save();

            // create event.
            PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

            /*
             * Create event!
             */
            //Event::fire('piggy_bank.addMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Added ' . Amount::format($amount, false) . ' to "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not add ' . Amount::format($amount, false) . ' to "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggy-banks.index');
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(PiggyBank $piggyBank)
    {
        $amount = floatval(Input::get('amount'));

        $savedSoFar = $piggyBank->currentRelevantRep()->currentamount;

        if ($amount <= $savedSoFar) {
            $repetition = $piggyBank->currentRelevantRep();
            $repetition->currentamount -= $amount;
            $repetition->save();

            PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount * -1, 'piggy_bank_id' => $piggyBank->id]);

            /*
             * Create event!
             */
            //Event::fire('piggy_bank.removeMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Removed ' . Amount::format($amount, false) . ' from "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not remove ' . Amount::format($amount, false) . ' from "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggy-banks.index');
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @SuppressWarnings("Unused")
     *
     * @return \Illuminate\View\View
     */
    public function remove(PiggyBank $piggyBank)
    {
        return view('piggy-banks.remove', compact('piggyBank'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function show(PiggyBank $piggyBank)
    {

        /*
         * Some reminder debug stuff.
         */


        //        if($piggyBank->remind_me === true) {
        //            // need to determine sensible moment for a reminder
        //            // to occur.
        //            // loop back to today?
        //            // if no target date, just make reminders out from start date?
        //
        //            $start = $piggyBank->targetdate;
        //            if(is_null($start)) {
        //
        //            }
        //        }


        $events = $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();

        /*
         * Number of reminders:
         */

        $subTitle = e($piggyBank->name);

        return view('piggy-banks.show', compact('piggyBank', 'events', 'subTitle'));

    }

    /**
     *
     */
    public function store(PiggyBankFormRequest $request, PiggyBankRepositoryInterface $repository)
    {
        $piggyBankData = [
            'repeats'      => false,
            'name'         => $request->get('name'),
            'startdate'    => new Carbon,
            'account_id'   => intval($request->get('account_id')),
            'targetamount' => floatval($request->get('targetamount')),
            'targetdate'   => strlen($request->get('targetdate')) > 0 ? new Carbon($request->get('targetdate')) : null,
            'reminder'     => $request->get('reminder'),
            'remind_me'    => $request->get('remind_me'),
        ];

        $piggyBank = $repository->store($piggyBankData);

        Session::flash('success', 'Stored piggy bank "' . e($piggyBank->name) . '".');

        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('piggy-banks.create')->withInput();
        }


        return Redirect::route('piggy-banks.index');
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @return $this
     */
    public function update(PiggyBank $piggyBank, PiggyBankRepositoryInterface $repository, PiggyBankFormRequest $request)
    {
        $piggyBankData = [
            'repeats'      => false,
            'name'         => $request->get('name'),
            'startdate'    => is_null($piggyBank->startdate) ? $piggyBank->created_at : $piggyBank->startdate,
            'account_id'   => intval($request->get('account_id')),
            'targetamount' => floatval($request->get('targetamount')),
            'targetdate'   => strlen($request->get('targetdate')) > 0 ? new Carbon($request->get('targetdate')) : null,
            'reminder'     => $request->get('reminder'),
        ];

        $piggyBank = $repository->update($piggyBank, $piggyBankData);

        Session::flash('success', 'Updated piggy bank "' . e($piggyBank->name) . '".');

        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('piggy-banks.edit', $piggyBank->id);
        }


        return Redirect::route('piggy-banks.index');


    }



}
