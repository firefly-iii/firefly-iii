<?php

/**
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class BudgetControllerCest
 */
class BudgetControllerCest
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
    public function amount(FunctionalTester $I)
    {
        $I->wantTo('update the amount for a budget and limit repetition');
        $I->amOnPage('/budgets/income');
    }

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a budget');
        $I->amOnRoute('budgets.create');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a budget');
        $I->amOnPage('/budgets/delete/1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a budget');
    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a budget');
        $I->amOnPage('/budgets/edit/1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('show all budgets');
        $I->amOnPage('/budgets');
    }

    /**
     * @param FunctionalTester $I
     */
    public function postUpdateIncome(FunctionalTester $I)
    {
        $I->wantTo('process the update to my monthly income');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        $I->wantTo('show a budget');
        $I->amOnPage('/budgets/show/1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a budget');
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a budget');
    }

    /**
     * @param FunctionalTester $I
     */
    public function updateIncome(FunctionalTester $I)
    {
        $I->wantTo('update my monthly income');
    }
}