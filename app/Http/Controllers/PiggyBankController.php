<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests;
use FireflyIII\Http\Controllers\Controller;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;
use View;
use Auth;
use Illuminate\Support\Collection;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Account;
use Steam;

/**
 * Class PiggyBankController
 *
 * @package FireflyIII\Http\Controllers
 */
class PiggyBankController extends Controller {

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Piggy banks');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');
    }


    /**
     * @return $this
     */
    public function index(AccountRepositoryInterface $repository)
    {
        /** @var Collection $piggyBanks */
        $piggyBanks = Auth::user()->piggyBanks()->where('repeats',0)->get();

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



}
