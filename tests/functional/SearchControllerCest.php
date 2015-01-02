<?php

/**
 * Class SearchControllerCest
 */
class SearchControllerCest
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

    public function index(FunctionalTester $I)
    {
        $I->wantTo('search for "salary"');
        $I->amOnPage('/search?q=salary');
        $I->see('Transactions');
        $I->see('Results for "salary"');

    }

    public function indexNoQuery(FunctionalTester $I)
    {
        $I->wantTo('Search for empty string');
        $I->amOnPage('/search?q=');
        $I->see('Search for &quot;&quot;');

    }
}
