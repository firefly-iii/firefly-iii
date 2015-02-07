<?php

/**
 * Class ReportControllerCest
 *
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings("short")
 *
 */
class ReportControllerCest
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

    public function budget(FunctionalTester $I)
    {
        $I->wantTo('see a budget report');
        $I->amOnPage('/reports/budget/2014/9');
        $I->see('Budget report for September 2014');
    }

    public function budgetInvalidDate(FunctionalTester $I)
    {
        $I->wantTo('see a budget report for an invalid date');
        $I->amOnPage('/reports/budget/XXXX/XX');
        $I->see('Invalid date');
    }

    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all possible reports');
        $I->amOnPage('/reports');
        $I->see('Reports');
        $I->see('Monthly reports');
        $I->see('Budget reports');
    }

    public function month(FunctionalTester $I)
    {
        $I->wantTo('see a monthly report');
        $I->amOnPage('/reports/2014/9');
        $I->see('Report for September 2014');
    }

    public function monthInvalidDate(FunctionalTester $I)
    {
        $I->wantTo('see a monthly report for an invalid month');
        $I->amOnPage('/reports/XXXX/XX');
        $I->see('Invalid date');
    }

    public function year(FunctionalTester $I)
    {
        $I->wantTo('see a yearly report');
        $I->amOnPage('/reports/2014');
        $I->see('Income vs. expenses');
        $I->see('Account balance');
    }

    public function yearInvalidDate(FunctionalTester $I)
    {
        $I->wantTo('see a yearly report for an invalid year');
        $I->amOnPage('/reports/XXXX');
        $I->see('Invalid date');
    }

}
