<?php

/**
 * Class RecurringControllerCest
 */
class RecurringControllerCest
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
        $I->wantTo('create a recurring transaction');
        $I->amOnPage('/recurring/create');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a recurring transaction');
        $I->amOnPage('/recurring/delete/1');
        $I->see('Delete "Huur"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a recurring transaction');
        $I->amOnPage('/recurring/delete/1');
        $I->see('Delete "Huur"');
        $I->submitForm('#destroy', []);
        $I->see('The recurring transaction was deleted.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a recurring transaction');
        $I->amOnPage('/recurring/edit/1');


    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all recurring transactions');
        $I->amOnPage('/recurring');
    }

    /**
     * @param FunctionalTester $I
     */
    public function rescan(FunctionalTester $I)
    {
        $I->wantTo('rescan a recurring transaction');
        $I->amOnPage('/recurring/rescan/1');
        $I->see('Rescanned everything.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function rescanInactive(FunctionalTester $I)
    {
        $I->wantTo('rescan an inactive recurring transaction');
        $I->amOnPage('/recurring/rescan/2');
        $I->see('Inactive recurring transactions cannot be scanned.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('show a recurring transaction');
        $I->amOnPage('/recurring/show/1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a recurring transaction');
        $I->amOnPage('/recurring/create');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction');
        $I->amOnPage('/recurring/edit/1');
    }

}