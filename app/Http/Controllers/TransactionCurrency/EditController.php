<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\TransactionCurrency;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EditController extends Controller
{
    protected CurrencyRepositoryInterface $repository;
    protected UserRepositoryInterface     $userRepository;

    /**
     * CurrencyController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.currencies'));
                app('view')->share('mainTitleIcon', 'fa-usd');
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function edit(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info(sprintf('Tried to edit currency %s but is not owner.', $currency->code));

            return redirect(route('currencies.index'));
        }

        $subTitleIcon     = 'fa-pencil';
        $subTitle         = (string)trans('breadcrumbs.edit_currency', ['name' => $currency->name]);
        $currency->symbol = htmlentities($currency->symbol);

        // is currently enabled (for this user?)
        $userCurrencies = $this->repository->get()->pluck('id')->toArray();
        $enabled        = in_array($currency->id, $userCurrencies, true);

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'enabled' => $hasOldInput ? (bool)$request->old('enabled') : $enabled,
        ];

        $request->session()->flash('preFilled', $preFilled);
        Log::channel('audit')->info('Edit currency.', $currency->toArray());

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('currencies.edit.fromUpdate')) {
            $this->rememberPreviousUrl('currencies.edit.url');
        }
        $request->session()->forget('currencies.edit.fromUpdate');

        return view('currencies.edit', compact('currency', 'subTitle', 'subTitleIcon'));
    }


    /**
     * Updates a currency.
     *
     * @param CurrencyFormRequest $request
     * @param TransactionCurrency $currency
     *
     * @return RedirectResponse|Redirector
     */
    public function update(CurrencyFormRequest $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        $data = $request->getCurrencyData();

        if (false === $data['enabled'] && $this->repository->currencyInUse($currency)) {
            $data['enabled'] = true;
        }

        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info('Tried to update (POST) currency without admin rights.', $data);

            return redirect(route('currencies.index'));
        }
        $currency = $this->repository->update($currency, $data);
        Log::channel('audit')->info('Updated (POST) currency.', $data);
        $request->session()->flash('success', (string)trans('firefly.updated_currency', ['name' => $currency->name]));
        app('preferences')->mark();

        if (1 === (int)$request->get('return_to_edit')) {
            $request->session()->put('currencies.edit.fromUpdate', true);

            return redirect(route('currencies.edit', [$currency->id]));
        }

        return redirect($this->getPreviousUrl('currencies.edit.url'));
    }

}
