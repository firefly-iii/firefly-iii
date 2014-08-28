<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\Component\ComponentRepositoryInterface as CRI;

/**
 * Class JsonController
 */
class JsonController extends BaseController
{
    protected $_accounts;
    protected $_components;
    protected $_categories;
    protected $_budgets;

    /**
     * @param ARI $accounts
     * @param CRI $components
     * @param Cat $categories
     * @param Bud $budgets
     */
    public function __construct(ARI $accounts, CRI $components, Cat $categories, Bud $budgets)
    {
        $this->_components = $components;
        $this->_accounts = $accounts;
        $this->_categories = $categories;
        $this->_budgets = $budgets;
    }

    /**
     * Returns a JSON list of all beneficiaries.
     */
    public function beneficiaries()
    {
        $list = $this->_accounts->getBeneficiaries();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Responds some JSON for typeahead fields.
     */
    public function categories()
    {
        $list = $this->_categories->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);


    }
} 