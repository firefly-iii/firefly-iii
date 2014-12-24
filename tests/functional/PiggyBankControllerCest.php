<?php

/**
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class PiggybankControllerCest
 */
class PiggyBankControllerCest
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
    public function add(FunctionalTester $I)
    {
        $I->wantTo('add money to a piggy bank');
        $I->amOnPage('/piggybanks/add/1');
        $I->see('Add money to New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a piggy bank');
        $I->amOnPage('/piggybanks/create');
        $I->see('Create new piggy bank');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a piggy bank');
        $I->amOnPage('/piggybanks/delete/1');
        $I->see('Delete &quot;New camera&quot;');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a piggy bank');
        $I->amOnPage('/piggybanks/delete/1');
        $I->see('Delete &quot;New camera&quot;');
        $I->submitForm('#destroy', []);
        $I->see('Piggy bank &quot;New camera&quot; deleted.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a piggy bank');
        $I->amOnPage('/piggybanks/edit/1');
        $I->see('Edit piggy bank "New camera"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function editWithTargetDate(FunctionalTester $I)
    {
        $I->wantTo('edit a piggy bank with a target date');
        $I->amOnPage('/piggybanks/edit/2');
        $I->see('Edit piggy bank "New clothes"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('view all piggy banks');
        $I->amOnPage('/piggybanks');
        $I->see('Piggy banks');
        $I->see('New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postAdd(FunctionalTester $I)
    {
        $I->wantTo('process adding money to a piggy bank');
        $I->amOnPage('/piggybanks/add/1');
        $I->see('Add money to New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postRemove(FunctionalTester $I)
    {
        $I->wantTo('process removing money from a piggy bank');
        $I->amOnPage('/piggybanks/remove/1');
        $I->see('Remove money from New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function remove(FunctionalTester $I)
    {
        $I->wantTo('removing money from a piggy bank');
        $I->amOnPage('/piggybanks/remove/1');
        $I->see('Remove money from New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('view a piggy bank');
        $I->amOnPage('/piggybanks/show/1');
        $I->see('New camera');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a new piggy bank');
        $I->amOnPage('/piggybanks/create');
        $I->see('Create new piggy bank');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a piggy bank');
        $I->amOnPage('/piggybanks/edit/1');
        $I->see('Edit piggy bank "New camera"');
    }

}