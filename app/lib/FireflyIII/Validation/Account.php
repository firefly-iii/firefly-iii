<?php

namespace FireflyIII\Validation;

use Illuminate\Support\MessageBag;

/**
 * Class Account
 *
 * @package FireflyIII\Validation
 */
class Account implements Validation
{
    /**
     * Every time a new [object or set of objects] is created through
     * the Firefly III website, the data submitted will be validated using
     * this method. This method does not check for valid models but rather if the information
     * in the array can be used to create the [object or set of objects] that the user wants to.
     *
     * For example, to create a new asset account with an opening balance, the user does not have to
     * submit an account_type or transaction_type because we know what to do.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function store(array $data = [])
    {
        $meta = join(',', array_keys(\Config::get('firefly.accountRoles')));

        $rules     = [
            'what'               => 'required|in:asset,expense,revenue',
            'name'               => 'required|between:1,100',
            'openingBalance'     => 'numeric',
            'openingBalanceDate' => 'date',
            'active'             => 'required|boolean',
            'account_role'       => 'in:' . $meta,
        ];
        $validator = \Validator::make($data, $rules);
        $validator->valid();

        return $validator->messages();
    }

    /**
     * Every time an [object or set of objects] is updated this method will validate the new
     * values in the context of the existing object (or set of objects). Since most forms
     * only have one [object] to validate and at least always one main [object] to validate
     * this method will accept an array of data to validate and an optional model to validate
     * against.
     *
     * @param array     $data
     * @param \Eloquent $model
     *
     * @return MessageBag
     */
    public function update(array $data = [], \Eloquent $model = null)
    {
        // this method simply returns the validation done by "store":
        return $this->store($data);
    }
}