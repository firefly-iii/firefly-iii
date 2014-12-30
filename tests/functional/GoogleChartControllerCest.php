<?php

/**
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 * Class GoogleChartControllerCest
 */
class GoogleChartControllerCest
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
    public function accountAllBalanceChart(FunctionalTester $I)
    {
        $I->wantTo('see the complete balance chart of an account.');
        $I->amOnPage('chart/account/1/all');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function accountBalanceChart(FunctionalTester $I)
    {
        $I->wantTo('see the session balance chart of an account.');
        $I->amOnPage('chart/account/1/session');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function allAccountsBalanceChart(FunctionalTester $I)
    {
        $I->wantTo('see the chart with the balances of all accounts');
        $I->amOnPage('/chart/home/account');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function allBudgetsHomeChart(FunctionalTester $I)
    {
        $I->wantTo('see the chart with all budgets on it');
        $I->amOnPage('/chart/home/budgets');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function allCategoriesHomeChart(FunctionalTester $I)
    {
        $I->wantTo('see the chart with all categories on it');
        $I->amOnPage('/chart/home/categories');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function billOverview(FunctionalTester $I)
    {
        $I->wantTo('see the chart for the history of a bill');
        $I->amOnPage('/chart/bills/1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function billsOverview(FunctionalTester $I)
    {
        $I->wantTo('see the chart for which bills I have yet to pay');
        $I->amOnPage('/chart/home/bills');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function budgetLimitSpending(FunctionalTester $I)
    {
        $I->wantTo('see the chart for a budget and a repetition');
        $I->amOnPage('/chart/budget/1/1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function budgetsAndSpending(FunctionalTester $I)
    {
        $I->wantTo('see the chart for a budget in a specific year');
        $I->amOnPage('/chart/budget/1/spending/2014');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function budgetsAndSpendingInvalidYear(FunctionalTester $I)
    {
        $I->wantTo('see the chart for a budget in an invalid year');
        $I->amOnPage('/chart/budget/1/spending/XXXX');
        $I->seeResponseCodeIs(200);
        $I->see('Invalid year');
    }

    /**
     * @param FunctionalTester $I
     */
    public function categoriesAndSpending(FunctionalTester $I)
    {
        $I->wantTo('see the chart for a category in a specific year');
        $I->amOnPage('/chart/category/1/spending/2014');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function categoriesAndSpendingInvalidYear(FunctionalTester $I)
    {
        $I->wantTo('see the chart for a category in an invalid year');
        $I->amOnPage('/chart/category/1/spending/XXXX');
        $I->seeResponseCodeIs(200);
        $I->see('Invalid year');
    }

    /**
     * @param FunctionalTester $I
     */
    public function emptyBillOverview(FunctionalTester $I)
    {
        $I->wantTo('see the chart for the history of an empty bill');
        $I->amOnPage('/chart/bills/2');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function piggyBankHistory(FunctionalTester $I)
    {
        $I->wantTo('see the chart for the history of a piggy bank');
        $I->amOnPage('/chart/piggy_history/1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function yearInExp(FunctionalTester $I)
    {
        $I->wantTo("see this year's expenses");
        $I->amOnPage('/chart/reports/income-expenses/' . date('Y'));
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function yearInExpInvalidYear(FunctionalTester $I)
    {
        $I->wantTo("see the year's expenses of an invalid year");
        $I->amOnPage('/chart/reports/income-expenses/XXXXX');
        $I->seeResponseCodeIs(200);
        $I->see('Invalid year');
    }

    /**
     * @param FunctionalTester $I
     */
    public function yearInExpSum(FunctionalTester $I)
    {
        $I->wantTo("see this year's expenses summarized");
        $I->amOnPage('/chart/reports/income-expenses-sum/' . date('Y'));
        $I->seeResponseCodeIs(200);
    }

    /**
     * @param FunctionalTester $I
     */
    public function yearInExpSumInvalidYear(FunctionalTester $I)
    {
        $I->wantTo("see the year's expenses summarized of an invalid year");
        $I->amOnPage('/chart/reports/income-expenses-sum/XXXXX');
        $I->seeResponseCodeIs(200);
        $I->see('Invalid year');
    }


}