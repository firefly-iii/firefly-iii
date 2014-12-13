<?php
use Carbon\Carbon;

/**
 * Class AccountControllerCest
 */
class AccountControllerCest
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

        Session::put('start', new Carbon);
        Session::put('end', new Carbon);


    }

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        // @codingStandardsIgnoreStart
        $I->wantTo('create a new asset account');
        $I->amOnPage('/accounts/create/asset');
        $I->see('Create a new asset account');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete an asset account');
        $I->amOnPage('/accounts/delete/3');
        $I->see('Delete account "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy an asset account');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('delete an asset account');
        $I->amOnPage('/accounts/edit/3');
        $I->see('Edit asset account "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see a list of accounts');
        $I->amOnPage('/accounts/asset');
        $I->see('Checking account');
        $I->see('Delete me');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('see one account');
        #$I->amOnPage('/accounts/show/3');
        #$I->see('Details for');
        #$I->see('Delete me');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a new asset account');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update an asset account');
    }

}