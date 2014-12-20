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
        $I->amOnPage('/budgets');

        ///budgets/income

        $I->sendAjaxPostRequest('/budgets/amount/1', ['amount' => 100]);
        $I->canSeeResponseCodeIs(200);
        $I->see('Groceries');
        $I->seeInDatabase('budgets', ['id' => 1]);
        #$I->seeInDatabase('budget_limits', ['budget_id' => 1, 'amount' => 100.00]);
    }

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a budget');
        $I->amOnRoute('budgets.create');
        $I->see('Create a new budget');
    }

    /**
     * @param FunctionalTester $I
     */
    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a budget');
        $I->amOnPage('/budgets/delete/3');
        $I->see('Delete budget "Delete me"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a budget');
        $I->amOnPage('/budgets/delete/3');
        $I->see('Delete budget "Delete me"');
        $I->submitForm('#destroy', []);
        $I->see('Budget &quot;Delete me&quot; was deleted.');
        #$I->dontSeeInDatabase('budgets', ['name' => 'Delete me','deleted_at' => null]);
        resetToClean::clean();
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
        $date = date('FY');
        $I->wantTo('process the update to my monthly income');
        $I->amOnPage('/budgets/income');
        $I->see('Update (expected) income for');
        $I->submitForm('#income', ['amount' => 1200]);
        $I->seeRecord('preferences', ['name' => 'budgetIncomeTotal' . $date, 'data' => 1200]);
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