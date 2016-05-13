<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Log;
use Preferences;
use Session;
use Steam;
use URL;
use View;

/**
 *
 *
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
        parent::__construct();
        View::share('title', trans('firefly.piggyBanks'));
        View::share('mainTitleIcon', 'fa-sort-amount-asc');
    }

    /**
     * Add money to piggy bank
     *
     * @param ARI       $repository
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function add(ARI $repository, PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $repository->leftOnAccount($piggyBank->account, $date);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount'));
    }

    /**
     * @param ARI $repository
     *
     * @return mixed
     */
    public function create(ARI $repository)
    {

        $periods      = config('firefly.piggy_bank_periods');
        $accounts     = ExpandedForm::makeSelectList($repository->getAccountsByType(['Default account', 'Asset account']));
        $subTitle     = trans('firefly.new_piggy_bank');
        $subTitleIcon = 'fa-plus';

        // put previous url in session if not redirect from store (not "create another").
        if (session('piggy-banks.create.fromStore') !== true) {
            Session::put('piggy-banks.create.url', URL::previous());
        }
        Session::forget('piggy-banks.create.fromStore');
        Session::flash('gaEventCategory', 'piggy-banks');
        Session::flash('gaEventAction', 'create');

        return view('piggy-banks.create', compact('accounts', 'periods', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]);

        // put previous url in session
        Session::put('piggy-banks.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'piggy-banks');
        Session::flash('gaEventAction', 'delete');

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {


        Session::flash('success', strval(trans('firefly.deleted_piggy_bank', ['name' => e($piggyBank->name)])));
        Preferences::mark();
        $repository->destroy($piggyBank);

        return redirect(session('piggy-banks.delete.url'));
    }

    /**
     * @param ARI       $repository
     * @param PiggyBank $piggyBank
     *
     * @return View
     */
    public function edit(ARI $repository, PiggyBank $piggyBank)
    {

        $periods      = config('firefly.piggy_bank_periods');
        $accounts     = ExpandedForm::makeSelectList($repository->getAccountsByType(['Default account', 'Asset account']));
        $subTitle     = trans('firefly.update_piggy_title', ['name' => $piggyBank->name]);
        $subTitleIcon = 'fa-pencil';
        $targetDate   = null;
        /*
         * Flash some data to fill the form.
         */
        if (!is_null($piggyBank->targetdate) || !$piggyBank->targetdate == '') {
            $targetDate = new Carbon($piggyBank->targetdate);
            $targetDate = $targetDate->format('Y-m-d');
        }

        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
        ];
        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'piggy-banks');
        Session::flash('gaEventAction', 'edit');

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('piggy-banks.edit.fromUpdate') !== true) {
            Session::put('piggy-banks.edit.url', URL::previous());
        }
        Session::forget('piggy-banks.edit.fromUpdate');

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @param ARI                          $repository
     * @param PiggyBankRepositoryInterface $piggyRepository
     *
     * @return View
     */
    public function index(ARI $repository, PiggyBankRepositoryInterface $piggyRepository)
    {
        /** @var Collection $piggyBanks */
        $piggyBanks = $piggyRepository->getPiggyBanks();
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        $accounts = [];
        /** @var PiggyBank $piggyBank */
        foreach ($piggyBanks as $piggyBank) {
            $piggyBank->savedSoFar = round($piggyBank->currentRelevantRep()->currentamount, 2);
            $piggyBank->percentage = $piggyBank->savedSoFar != 0 ? intval($piggyBank->savedSoFar / $piggyBank->targetamount * 100) : 0;
            $piggyBank->leftToSave = bcsub($piggyBank->targetamount, $piggyBank->savedSoFar);
            $piggyBank->percentage = $piggyBank->percentage > 100 ? 100 : $piggyBank->percentage;

            /*
             * Fill account information:
             */
            $account = $piggyBank->account;
            if (!isset($accounts[$account->id])) {
                $accounts[$account->id] = [
                    'name'              => $account->name,
                    'balance'           => Steam::balanceIgnoreVirtual($account, $end),
                    'leftForPiggyBanks' => $repository->leftOnAccount($account, $end),
                    'sumOfSaved'        => strval($piggyBank->savedSoFar),
                    'sumOfTargets'      => strval(round($piggyBank->targetamount, 2)),
                    'leftToSave'        => $piggyBank->leftToSave,
                ];
            } else {
                $accounts[$account->id]['sumOfSaved']   = bcadd($accounts[$account->id]['sumOfSaved'], $piggyBank->savedSoFar);
                $accounts[$account->id]['sumOfTargets'] = bcadd($accounts[$account->id]['sumOfTargets'], $piggyBank->targetamount);
                $accounts[$account->id]['leftToSave']   = bcadd($accounts[$account->id]['leftToSave'], $piggyBank->leftToSave);
            }
        }

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     */
    public function order(PiggyBankRepositoryInterface $repository)
    {
        $data = Input::get('order');

        // set all users piggy banks to zero:
        $repository->reset();


        if (is_array($data)) {
            foreach ($data as $order => $id) {
                $repository->setOrder(intval($id), ($order + 1));
            }
        }
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param ARI                          $accounts
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(PiggyBankRepositoryInterface $repository, ARI $accounts, PiggyBank $piggyBank)
    {
        $amount = round(Input::get('amount'), 2);
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $accounts->leftOnAccount($piggyBank->account, $date);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = round(min($leftOnAccount, $leftToSave), 2);

        if ($amount <= $maxAmount) {
            $repetition                = $piggyBank->currentRelevantRep();
            $repetition->currentamount = bcadd($repetition->currentamount, $amount);
            $repetition->save();

            // create event
            $repository->createEvent($piggyBank, $amount);

            Session::flash(
                'success', strval(trans('firefly.added_amount_to_piggy', ['amount' => Amount::format($amount, false), 'name' => e($piggyBank->name)]))
            );
            Preferences::mark();

            return redirect(route('piggy-banks.index'));
        }

        Log::error('Cannot add ' . $amount . ' because max amount is ' . $maxAmount . ' (left on account is ' . $leftOnAccount . ')');
        Session::flash('error', strval(trans('firefly.cannot_add_amount_piggy', ['amount' => Amount::format($amount, false), 'name' => e($piggyBank->name)])));

        return redirect(route('piggy-banks.index'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $amount = round(Input::get('amount'), 2);

        $savedSoFar = $piggyBank->currentRelevantRep()->currentamount;

        if ($amount <= $savedSoFar) {
            $repetition                = $piggyBank->currentRelevantRep();
            $repetition->currentamount = bcsub($repetition->currentamount, $amount);
            $repetition->save();

            // create event
            $repository->createEvent($piggyBank, bcmul($amount, '-1'));

            Session::flash(
                'success', strval(trans('firefly.removed_amount_from_piggy', ['amount' => Amount::format($amount, false), 'name' => e($piggyBank->name)]))
            );
            Preferences::mark();

            return redirect(route('piggy-banks.index'));
        }

        Session::flash('error', strval(trans('firefly.cannot_remove_from_piggy', ['amount' => Amount::format($amount, false), 'name' => e($piggyBank->name)])));

        return redirect(route('piggy-banks.index'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     *
     * @return \Illuminate\View\View
     */
    public function remove(PiggyBank $piggyBank)
    {
        return view('piggy-banks.remove', compact('piggyBank'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return View
     */
    public function show(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $events   = $repository->getEvents($piggyBank);
        $subTitle = e($piggyBank->name);

        return view('piggy-banks.show', compact('piggyBank', 'events', 'subTitle'));

    }

    /**
     * @param PiggyBankFormRequest         $request
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(PiggyBankFormRequest $request, PiggyBankRepositoryInterface $repository)
    {

        $piggyBankData = [
            'name'          => $request->get('name'),
            'startdate'     => new Carbon,
            'account_id'    => intval($request->get('account_id')),
            'targetamount'  => round($request->get('targetamount'), 2),
            'remind_me'     => false,
            'reminder_skip' => 0,
            'order'         => $repository->getMaxOrder() + 1,
            'targetdate'    => strlen($request->get('targetdate')) > 0 ? new Carbon($request->get('targetdate')) : null,
        ];

        $piggyBank = $repository->store($piggyBankData);

        Session::flash('success', strval(trans('firefly.stored_piggy_bank', ['name' => e($piggyBank->name)])));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            Session::put('piggy-banks.create.fromStore', true);

            return redirect(route('piggy-banks.create'))->withInput();
        }


        // redirect to previous URL.
        return redirect(session('piggy-banks.create.url'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBankFormRequest         $request
     * @param PiggyBank                    $piggyBank
     *
     * @return $this
     */
    public function update(PiggyBankRepositoryInterface $repository, PiggyBankFormRequest $request, PiggyBank $piggyBank)
    {
        $piggyBankData = [
            'name'          => $request->get('name'),
            'startdate'     => is_null($piggyBank->startdate) ? $piggyBank->created_at : $piggyBank->startdate,
            'account_id'    => intval($request->get('account_id')),
            'targetamount'  => round($request->get('targetamount'), 2),
            'remind_me'     => false,
            'reminder_skip' => 0,
            'targetdate'    => strlen($request->get('targetdate')) > 0 ? new Carbon($request->get('targetdate')) : null,
        ];

        $piggyBank = $repository->update($piggyBank, $piggyBankData);

        Session::flash('success', strval(trans('firefly.updated_piggy_bank', ['name' => e($piggyBank->name)])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('piggy-banks.edit.fromUpdate', true);

            return redirect(route('piggy-banks.edit', [$piggyBank->id]));
        }


        // redirect to previous URL.
        return redirect(session('piggy-banks.edit.url'));


    }


}
