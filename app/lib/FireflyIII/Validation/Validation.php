<?php

namespace FireflyIII\Validation;

use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface Validation
 *
 * @package FireflyIII\Validation
 */
interface Validation
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
    public function store(array $data = []);

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
    public function update(array $data = [], Model $model = null);

}