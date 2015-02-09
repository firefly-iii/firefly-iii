<?php

namespace FireflyIII\Repositories\Account;

use App;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Class AccountRepository
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountRepository implements AccountRepositoryInterface
{

    /**
     * @param array $data
     *
     * @return Account;
     */
    public function store(array $data)
    {
        $newAccount = $this->_store($data);


        // continue with the opposing account:
        if ($data['openingBalance'] != 0) {
            $type     = $data['openingBalance'] < 0 ? 'expense' : 'revenue';
            $opposing = [
                'user'        => $data['user'],
                'accountType' => $type,
                'name'        => $data['name'] . ' initial balance',
                'active'      => false,
            ];
            $this->_store($opposing);
        }

        return $newAccount;

    }

    /**
     * @param array $data
     */
    protected function _store(array $data)
    {
        $accountType = AccountType::whereType($data['accountType'])->first();
        $newAccount  = new Account(
            [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'name'            => $data['name'],
                'active'          => $data['active'] === true ? true : false,
            ]
        );
        if (!$newAccount->isValid()) {
            App::abort(500);
        }
        $newAccount->save();
    }

}