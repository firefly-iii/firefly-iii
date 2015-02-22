<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Input;
use Preferences;
use Session;
use View;
use Response;
use Log;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers
 */
class BudgetController extends Controller
{

    public function __construct()
    {
        View::share('title', 'Budgets');
        View::share('mainTitleIcon', 'fa-tasks');
    }

    /**
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function amount(Budget $budget, BudgetRepositoryInterface $repository)
    {
        $amount          = intval(Input::get('amount'));
        $date            = Session::get('start', Carbon::now()->startOfMonth());
        Log::debug('Budget: '. $budget->id);
        Log::debug('Budget (full) ' . print_r($budget->toArray(),true));
        Log::debug('Amount:' . $amount);
        Log::debug('Date: ' . $date);
        $limitRepetition = $repository->updateLimitAmount($budget, $date, $amount);

        return Response::json(['name' => $budget->name, 'repetition' => $limitRepetition ? $limitRepetition->id : 0]);

    }

    /**
     * @return mixed
     */
    public function index(BudgetRepositoryInterface $repository)
    {
        $budgets = Auth::user()->budgets()->get();

        // loop the budgets:
        $budgets->each(
            function (Budget $budget) use ($repository) {
                $date               = Session::get('start', Carbon::now()->startOfMonth());
                $budget->spent      = $repository->spentInMonth($budget, $date);
                $budget->currentRep = $budget->limitrepetitions()->where('limit_repetitions.startdate', $date)->first(['limit_repetitions.*']);
            }
        );

        $date          = Session::get('start', Carbon::now()->startOfMonth())->format('FY');
        $spent         = $budgets->sum('spent');
        $amount        = Preferences::get('budgetIncomeTotal' . $date, 1000)->data;
        $overspent     = $spent > $amount;
        $spentPCT      = $overspent ? ceil($amount / $spent * 100) : ceil($spent / $amount * 100);
        $budgetMax     = Preferences::get('budgetMaximum', 1000);
        $budgetMaximum = $budgetMax->data;

        return View::make('budgets.index', compact('budgetMaximum', 'budgets', 'spent', 'spentPCT', 'overspent', 'amount'));
    }

}
