<?php namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Http\Requests;
use Illuminate\Pagination\LengthAwarePaginator;
use View;


/**
 * Class TransactionController
 *
 * @package FireflyIII\Http\Controllers
 */
class TransactionController extends Controller
{
    /**
     */
    public function __construct()
    {
        View::share('title', 'Transactions');
        View::share('mainTitleIcon', 'fa-repeat');
    }

    /**
     * @param $what
     *
     * @return $this
     */
    public function index($what)
    {
        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $subTitleIcon = 'fa-long-arrow-left';
                $subTitle     = 'Expenses';
                //$journals     = $this->_repository->getWithdrawalsPaginated(50);
                $types = ['Withdrawal'];
                break;
            case 'revenue':
            case 'deposit':
                $subTitleIcon = 'fa-long-arrow-right';
                $subTitle     = 'Revenue, income and deposits';
                //                $journals     = $this->_repository->getDepositsPaginated(50);
                $types = ['Deposit'];
                break;
            case 'transfer':
            case 'transfers':
                $subTitleIcon = 'fa-arrows-h';
                $subTitle     = 'Transfers';
                //$journals     = $this->_repository->getTransfersPaginated(50);
                $types = ['Transfer'];
                break;
        }

        $page   = intval(\Input::get('page'));
        $offset = $page > 0 ? ($page - 1) * 50 : 0;

        $set      = Auth::user()->transactionJournals()->transactionTypes($types)->withRelevantData()->take(50)->offset($offset)->orderBy('date', 'DESC')->get(
            ['transaction_journals.*']
        );
        $count    = Auth::user()->transactionJournals()->transactionTypes($types)->count();
        $journals = new LengthAwarePaginator($set, $count, 50, $page);
        $journals->setPath('transactions/' . $what);

        return View::make('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }

}
