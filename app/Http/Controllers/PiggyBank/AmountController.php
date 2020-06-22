<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

/**
 * Class AmountController
 */
class AmountController extends Controller
{

    private AccountRepositoryInterface   $accountRepos;
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-bullseye');

                $this->piggyRepos   = app(PiggyBankRepositoryInterface::class);
                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Add money to piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function add(PiggyBank $piggyBank)
    {
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, new Carbon);
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);
        $currency      = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount', 'currency'));
    }

    /**
     * Add money to piggy bank (for mobile devices).
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function addMobile(PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date          = session('end', new Carbon);
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $date);
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);
        $currency      = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.add-mobile', compact('piggyBank', 'maxAmount', 'currency'));
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
        $amount   = $request->get('amount') ?? '0';
        $currency = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();
        // if amount is negative, make positive and continue:
        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }
        if ($this->piggyRepos->canAddAmount($piggyBank, $amount)) {
            $this->piggyRepos->addAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string) trans(
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
            (string) trans(
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
        $amount   = $request->get('amount') ?? '0';
        $currency = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();
        // if amount is negative, make positive and continue:
        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }
        if ($this->piggyRepos->canRemoveAmount($piggyBank, $amount)) {
            $this->piggyRepos->removeAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string) trans(
                    'firefly.removed_amount_from_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }

        $amount = (string) round($request->get('amount'), 12);

        session()->flash(
            'error',
            (string) trans(
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
     * @return Factory|View
     */
    public function remove(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        $currency   = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.remove', compact('piggyBank', 'repetition', 'currency'));
    }

    /**
     * Remove money from piggy bank (for mobile devices).
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function removeMobile(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        $currency   = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.remove-mobile', compact('piggyBank', 'repetition', 'currency'));
    }
}
