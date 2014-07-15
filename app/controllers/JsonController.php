<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\Component\ComponentRepositoryInterface as CRI;

class JsonController extends BaseController
{
    protected $accounts;
    protected $components;
    protected $categories;
    protected $budgets;

    public function __construct(ARI $accounts, CRI $components, Cat $categories, Bud $budgets)
    {
        $this->components = $components;
        $this->accounts = $accounts;
        $this->categories = $categories;
        $this->budgets = $budgets;
    }

    /**
     * Returns a JSON list of all beneficiaries.
     */
    public function beneficiaries()
    {
        $list = $this->accounts->getBeneficiaries();
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
        $list = $this->categories->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);


    }

} 