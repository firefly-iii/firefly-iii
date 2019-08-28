<?php
/**
 * PiggyBankController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class PiggyBankController.
 *
 */
class PiggyBankController extends Controller
{

    /** @var AccountRepositoryInterface The account repository */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var PiggyBankRepositoryInterface Piggy bank repository. */
    private $piggyRepos;

    /**
     * PiggyBankController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-sort-amount-asc');

                $this->piggyRepos    = app(PiggyBankRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);
                $this->accountRepos  = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Add money to piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function add(PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $date);
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);

        // get currency:
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount', 'currency'));
    }

    /**
     * Add money to piggy bank (for mobile devices).
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function addMobile(PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $date);
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);

        // get currency:
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }

        return view('piggy-banks.add-mobile', compact('piggyBank', 'maxAmount', 'currency'));
    }

    /**
     * Create a piggy bank.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = (string)trans('firefly.new_piggy_bank');
        $subTitleIcon = 'fa-plus';

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('piggy-banks.create.fromStore')) {
            $this->rememberPreviousUri('piggy-banks.create.uri');
        }
        session()->forget('piggy-banks.create.fromStore');

        return view('piggy-banks.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * Delete a piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = (string)trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]);

        // put previous url in session
        $this->rememberPreviousUri('piggy-banks.delete.uri');

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * Destroy the piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return RedirectResponse
     */
    public function destroy(PiggyBank $piggyBank): RedirectResponse
    {
        session()->flash('success', (string)trans('firefly.deleted_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();
        $this->piggyRepos->destroy($piggyBank);

        return redirect($this->getPreviousUri('piggy-banks.delete.uri'));
    }

    /**
     * Edit a piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(PiggyBank $piggyBank)
    {
        $subTitle     = (string)trans('firefly.update_piggy_title', ['name' => $piggyBank->name]);
        $subTitleIcon = 'fa-pencil';
        $targetDate   = null;
        $startDate    = null;
        $note         = $piggyBank->notes()->first();
        // Flash some data to fill the form.
        if (null !== $piggyBank->targetdate) {
            $targetDate = $piggyBank->targetdate->format('Y-m-d');
        }
        if (null !== $piggyBank->startdate) {
            $startDate = $piggyBank->startdate->format('Y-m-d');
        }

        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
                      'startdate'    => $startDate,
                      'notes'        => null === $note ? '' : $note->text,
        ];
        session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('piggy-banks.edit.fromUpdate')) {
            $this->rememberPreviousUri('piggy-banks.edit.uri');
        }
        session()->forget('piggy-banks.edit.fromUpdate');

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'preFilled'));
    }

    /**
     * Show overview of all piggy banks.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->piggyRepos->correctOrder();
        $collection = $this->piggyRepos->getPiggyBanks();
        $total      = $collection->count();
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $accounts   = [];
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        // transform piggies using the transformer:
        $parameters = new ParameterBag;
        $parameters->set('end', $end);
        $transformed = new Collection;

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag);

        /** @var AccountTransformer $accountTransformer */
        $accountTransformer = app(AccountTransformer::class);
        $accountTransformer->setParameters($parameters);
        /** @var PiggyBank $piggy */
        foreach ($collection as $piggy) {
            $array     = $transformer->transform($piggy);
            $account   = $accountTransformer->transform($piggy->account);
            $accountId = $account['id'];
            if (!isset($accounts[$accountId])) {
                // create new:
                $accounts[$accountId] = $account;

                // add some interesting details:
                $accounts[$accountId]['left']    = $accounts[$accountId]['current_balance'];
                $accounts[$accountId]['saved']   = 0;
                $accounts[$accountId]['target']  = 0;
                $accounts[$accountId]['to_save'] = 0;
            }

            // calculate new interesting fields:
            $accounts[$accountId]['left']    -= $array['current_amount'];
            $accounts[$accountId]['saved']   += $array['current_amount'];
            $accounts[$accountId]['target']  += $array['target_amount'];
            $accounts[$accountId]['to_save'] += ($array['target_amount'] - $array['current_amount']);
            $array['account_name']           = $account['name'];
            $transformed->push($array);
        }

        $transformed = $transformed->slice(($page - 1) * $pageSize, $pageSize);
        $piggyBanks  = new LengthAwarePaginator($transformed, $total, $pageSize, $page);
        $piggyBanks->setPath(route('piggy-banks.index'));

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * Add money to piggy bank.
     *
     * @param Request   $request
     * @param PiggyBank $piggyBank
     *
     * @return RedirectResponse
     */
    public function postAdd(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $amount     = $request->get('amount') ?? '0';
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }
        if ($this->piggyRepos->canAddAmount($piggyBank, $amount)) {
            $this->piggyRepos->addAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string)trans(
                    'firefly.added_amount_to_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }

        Log::error('Cannot add ' . $amount . ' because canAddAmount returned false.');
        session()->flash(
            'error',
            (string)trans(
                'firefly.cannot_add_amount_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => e($piggyBank->name)]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * Remove money from piggy bank.
     *
     * @param Request   $request
     * @param PiggyBank $piggyBank
     *
     * @return RedirectResponse
     */
    public function postRemove(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $amount     = $request->get('amount') ?? '0';
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }
        if ($this->piggyRepos->canRemoveAmount($piggyBank, $amount)) {
            $this->piggyRepos->removeAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string)trans(
                    'firefly.removed_amount_from_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }

        $amount = (string)round($request->get('amount'), 12);

        session()->flash(
            'error',
            (string)trans(
                'firefly.cannot_remove_from_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => e($piggyBank->name)]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * Remove money from piggy bank form.
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function remove(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        // get currency:
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }

        return view('piggy-banks.remove', compact('piggyBank', 'repetition', 'currency'));
    }

    /**
     * Remove money from piggy bank (for mobile devices).
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function removeMobile(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        // get currency:
        $currency   = app('amount')->getDefaultCurrency();
        // TODO we can use getAccountCurrency() instead
        $currencyId = (int)$this->accountRepos->getMetaValue($piggyBank->account, 'currency_id');
        if ($currencyId > 0) {
            $currency = $this->currencyRepos->findNull($currencyId);
        }


        return view('piggy-banks.remove-mobile', compact('piggyBank', 'repetition', 'currency'));
    }

    /**
     * Set the order of a piggy bank.
     *
     * @param Request   $request
     * @param PiggyBank $piggyBank
     *
     * @return JsonResponse
     */
    public function setOrder(Request $request, PiggyBank $piggyBank): JsonResponse
    {
        $newOrder = (int)$request->get('order');
        $this->piggyRepos->setOrder($piggyBank, $newOrder);

        return response()->json(['data' => 'OK']);
    }

    /**
     * Show a single piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(PiggyBank $piggyBank)
    {
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());
        // transform piggies using the transformer:
        $parameters = new ParameterBag;
        $parameters->set('end', $end);

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($parameters);
        $piggy    = $transformer->transform($piggyBank);
        $events   = $this->piggyRepos->getEvents($piggyBank);
        $subTitle = $piggyBank->name;

        return view('piggy-banks.show', compact('piggyBank', 'events', 'subTitle', 'piggy'));
    }

    /**
     * Store a new piggy bank.
     *
     * @param PiggyBankFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(PiggyBankFormRequest $request)
    {
        $data = $request->getPiggyBankData();
        if (null === $data['startdate']) {
            $data['startdate'] = new Carbon;
        }
        $piggyBank = $this->piggyRepos->store($data);

        session()->flash('success', (string)trans('firefly.stored_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('piggy-banks.create.uri'));

        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            session()->put('piggy-banks.create.fromStore', true);

            $redirect = redirect(route('piggy-banks.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }

    /**
     * Update a piggy bank.
     *
     * @param PiggyBankFormRequest $request
     * @param PiggyBank            $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(PiggyBankFormRequest $request, PiggyBank $piggyBank)
    {
        $data      = $request->getPiggyBankData();
        $piggyBank = $this->piggyRepos->update($piggyBank, $data);

        session()->flash('success', (string)trans('firefly.updated_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('piggy-banks.edit.uri'));

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('piggy-banks.edit.fromUpdate', true);

            $redirect = redirect(route('piggy-banks.edit', [$piggyBank->id]));
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
