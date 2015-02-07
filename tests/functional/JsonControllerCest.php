<?php

/**
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 * Class JsonControllerCest
 */
class JsonControllerCest
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
    public function categories(FunctionalTester $I)
    {
        $I->wantTo('See a JSON list of categories.');
        $I->amOnPage('/json/categories');
        $I->canSeeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function expenseAccounts(FunctionalTester $I)
    {
        $I->wantTo('See a JSON list of expense accounts.');
        $I->amOnPage('/json/expense-accounts');
        $I->canSeeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function revenueAccounts(FunctionalTester $I)
    {
        $I->wantTo('See a JSON list of revenue accounts.');
        $I->amOnPage('/json/revenue-accounts');
        $I->canSeeResponseCodeIs(200);
    }
}
