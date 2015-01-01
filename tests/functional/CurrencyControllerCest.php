<?php

/**
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class CurrencyControllerCest
 */
class CurrencyControllerCest
{
    /**
     * @param FunctionalTester $I
     */
    public function _after(FunctionalTester $I)
    {
    }

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedAs(['email' => 'thegrumpydictator@gmail.com', 'password' => 'james']);
    }


    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a currency');
        $I->amOnRoute('currency.create');
        $I->see('Create a new currency');
    }

    /**
     * @param FunctionalTester $I
     */
    public function defaultCurrency(FunctionalTester $I)
    {
        $I->wantTo('make US Dollar the default currency');
        $I->amOnPage('/currency/default/2');
        $I->see('US Dollar is now the default currency.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a currency');
        $I->amOnPage('/currency/delete/3');
        $I->see('Delete currency "Hungarian forint"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a currency');
        $I->amOnPage('/currency/delete/3');
        $I->see('Delete currency "Hungarian forint"');
        $I->submitForm('#destroy', []);
        $I->see('Currency &quot;Hungarian forint&quot; deleted');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroyFail(FunctionalTester $I)
    {
        $I->wantTo('destroy a currency currently in use');
        $I->amOnPage('/currency/delete/1');
        $I->see('Cannot delete Euro because there are still transactions attached to it.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a currency');
        $I->amOnPage('/currency/edit/2');
        $I->see('Edit currency "US Dollar"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function failUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a currency and fail');
        $I->amOnPage('/currency/edit/2');
        $I->see('Edit currency "US Dollar"');
        $I->submitForm('#update', ['name' => 'Failed update', 'code' => '123', 'post_submit_action' => 'update']);
        $I->dontSeeRecord('transaction_currencies', ['name' => 'Failed update']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('show all currencies');
        $I->amOnPage('/currency');
        $I->see('fa-usd');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->amOnPage('/currency/create');
        $I->wantTo('store a new currency');
        $I->see('Create a new currency');
        $I->submitForm('#store', ['name' => 'New currency.', 'symbol' => 'C', 'code' => 'CXX', 'post_submit_action' => 'store']);
        $I->seeRecord('transaction_currencies', ['name' => 'New currency.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeAndCreateAnother(FunctionalTester $I)
    {
        $I->amOnPage('/currency/create');
        $I->wantTo('store a new currency and create another');
        $I->see('Create a new currency');
        $I->submitForm('#store', ['name' => 'Store and create another.', 'symbol' => 'C', 'code' => 'CXX', 'post_submit_action' => 'create_another']);
        $I->seeRecord('transaction_currencies', ['name' => 'Store and create another.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeFail(FunctionalTester $I)
    {
        $I->amOnPage('/currency/create');
        $I->wantTo('make storing a new currency fail.');
        $I->see('Create a new currency');
        $I->submitForm('#store', ['name' => 'Store and fail', 'symbol' => null, 'code' => '123', 'post_submit_action' => 'store']);
        $I->dontSeeRecord('transaction_currencies', ['name' => 'Store and fail']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function storeValidateOnly(FunctionalTester $I)
    {
        $I->amOnPage('/currency/create');
        $I->wantTo('validate a new currency');
        $I->see('Create a new currency');
        $I->submitForm('#store', ['name' => 'Store validate only.', 'symbol' => 'C', 'code' => 'CXX', 'post_submit_action' => 'validate_only']);
        $I->dontSeeRecord('transaction_currencies', ['name' => 'Store validate only.']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a currency');
        $I->amOnPage('/currency/edit/2');
        $I->see('Edit currency "US Dollar"');
        $I->submitForm('#update', ['name' => 'Successful update', 'symbol' => '$', 'code' => 'USD', 'post_submit_action' => 'update']);
        $I->seeRecord('transaction_currencies', ['name' => 'Successful update']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturn(FunctionalTester $I)
    {
        $I->wantTo('update a currency and return to form');
        $I->amOnPage('/currency/edit/2');
        $I->see('Edit currency "US Dollar"');
        $I->submitForm(
            '#update', ['name' => 'US DollarXXX', 'symbol' => '$', 'code' => 'USD', 'post_submit_action' => 'return_to_edit']
        );
        $I->seeRecord('transaction_currencies', ['name' => 'US DollarXXX']);

    }

    /**
     * @param FunctionalTester $I
     */
    public function validateUpdateOnly(FunctionalTester $I)
    {
        $I->wantTo('update a currency and validate only');
        $I->amOnPage('/currency/edit/2');
        $I->see('Edit currency "US Dollar"');
        $I->submitForm('#update', ['name' => 'Update Validate Only', 'post_submit_action' => 'validate_only']);
        $I->dontSeeRecord('transaction_currencies', ['name' => 'Update Validate Only']);
        $I->seeRecord('transaction_currencies', ['name' => 'US Dollar']);

    }
}