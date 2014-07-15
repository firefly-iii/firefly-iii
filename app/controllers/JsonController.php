<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Component\ComponentRepositoryInterface as CRI;

class JsonController extends BaseController
{

    public function __construct(ARI $accounts,CRI $components)
    {
        $this->components = $components;
        $this->accounts = $accounts;
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
        $list = $this->components->get();


    }

} 