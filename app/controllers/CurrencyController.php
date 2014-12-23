<?php
use FireflyIII\Database\TransactionCurrency\TransactionCurrency as Repository;
/**
 * Class CurrencyController
 */
class CurrencyController extends BaseController
{

    /** @var Repository  */
    protected $_repository;

    public function __construct(Repository $repository)
    {
        $this->_repository = $repository;
        View::share('title', 'Currencies');
        View::share('mainTitleIcon', 'fa-usd');
    }

    public function index()
    {
        $currencies = $this->_repository->get();

        return View::make('currency.index',compact('currencies'));
    }

}